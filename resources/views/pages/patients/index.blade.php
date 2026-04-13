<?php

use Livewire\Component;
use App\Models\Patient;

new class extends Component {
    public string $search = '';

    public function render()
    {
        $patients = Patient::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%')->orWhere('nik', 'ilike', '%' . $this->search . '%');
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(25);

        return $this->view([
            'patients' => $patients,
        ]);
    }
};
?>

<div>
    <x-header header="Daftar Pasien" description="List pasien yang sudah terdaftar di sistem" />
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <x-input wire:model.live.debounce.100ms="search" name="search" placeholder="Cari pasien..."
            class="mb-4 md:max-w-lg w-full" />
        <x-button wire:click="openModal" class="mb-4" color="brand">Registrasi Baru</x-button>
    </div>
    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Nama / NIK
                    </th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Status
                        SATUSEHAT</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Phone</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        L/P</th>
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($patients as $patient)
                    <tr>
                        <td class="w-px whitespace-nowrap px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $patient->name }}</div>
                            <div class="text-xs text-gray-500">{{ $patient->nik }}</div>
                        </td>
                        <td class="w-px whitespace-nowrap px-6 py-4">
                            @if ($patient->satusehat_id)
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Terintegrasi ({{ $patient->satusehat_id }})
                                </span>
                            @else
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Belum Sync
                                </span>
                            @endif
                        </td>
                        <td class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-medium">
                            {{ $patient->phone_number }}</td>
                        <td class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-medium">
                            {{ $patient->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                        <td class="px-12 py-4 text-right text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900">Detail</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $patients->links() }}
    </div>
</div>
