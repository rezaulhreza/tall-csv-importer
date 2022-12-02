<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Customers') }}
            </h2>

            <button x-data x-on:click="window.livewire.emitTo('csv-importer', 'toggle')">Import</button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="space-y-1">
                        @foreach ($customers as $customer)
                            <div>
                                {{ $customer->id }}. {{ $customer->first_name }} {{ $customer->last_name }} {{ $customer->company }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <livewire:csv-importer :model="App\Models\Customer::class" :columnsToMap="['id', 'first_name', 'last_name', 'email']" :requiredColumns="['id', 'first_name', 'last_name', 'email']" :columnLabels="['id' => 'ID', 'first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email']" />
    </div>
</x-app-layout>
