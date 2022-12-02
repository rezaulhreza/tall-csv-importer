<?php

namespace App\Http\Livewire;

use App\Helpers\ChunkIterator;
use App\Jobs\ImportCsv;
use Illuminate\Support\Facades\Bus;
use League\Csv\Reader;
use League\Csv\Statement;
use Livewire\Component;
use Livewire\WithFileUploads;

class CsvImporter extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public $file;

    public string $model;

    public array $fileHeaders = [];

    public int $fileRowCount = 0;

    public array $columnsToMap = [];

    public array $requiredColumns = [];

    public array $columnLabels = [];

    protected $listeners = [
        'toggle'
    ];

    public function mount()
    {
        $this->columnsToMap = collect($this->columnsToMap)
            ->mapWithKeys(fn ($column) => [$column => ''])
            ->toArray();
    }

    public function rules()
    {
        $columnRules = collect($this->requiredColumns)
            ->mapWithKeys(function ($column) {
                return ['columnsToMap.' . $column => ['required']];
            })
            ->toArray();

        return array_merge($columnRules, [
            'file' => ['required', 'mimes:csv', 'max:51200'],
        ]);
    }

    public function validationAttributes()
    {
        return collect($this->requiredColumns)
            ->mapWithKeys(function ($column) {
                return ['columnsToMap.' . $column => strtolower($this->columnLabels[$column] ?? $column)];
            })
            ->toArray();
    }

    public function updatedFile()
    {
        $this->validateOnly('file');

        $csv = $this->readCsv;

        $this->fileHeaders = $csv->getHeader();

        $this->fileRowCount = count($this->csvRecords);

        $this->resetValidation();
    }

    public function getReadCsvProperty(): Reader
    {
        return $this->readCsv($this->file->getRealPath());
    }

    public function getCsvRecordsProperty()
    {
        return Statement::create()->process($this->readCsv);
    }

    public function import()
    {
        $this->validate();

        $import = $this->createImport();

        $batches = collect(
            (new ChunkIterator($this->csvRecords->getRecords(), 10))
                ->get()
        )->map(function ($chunk) use ($import) {
            return new ImportCsv($import, $this->model, $chunk, $this->columnsToMap);
        })
        ->toArray();

        Bus::batch($batches)
            ->finally(function () use ($import) {
                $import->touch('completed_at');
            })
            ->dispatch();

        $this->resetExcept(['model', 'columnsToMap', 'columnLabels', 'requiredColumns', 'open']);

        $this->emitTo('csv-imports', 'imports.refresh');
    }

    public function createImport()
    {
        return auth()->user()->imports()->create([
            'file_path' => $this->file->getRealPath(),
            'file_name' => $this->file->getClientOriginalName(),
            'total_rows' => count($this->csvRecords),
            'model' => $this->model,
        ]);
    }

    protected function readCsv(string $path): Reader
    {
        $stream = fopen($path, 'r');
        $csv = Reader::createFromStream($stream);
        $csv->setHeaderOffset(0);

        return $csv;
    }

    public function toggle()
    {
        $this->open = !$this->open;
    }

    public function render()
    {
        return view('livewire.csv-importer');
    }
}
