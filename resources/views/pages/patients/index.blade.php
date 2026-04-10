<?php

use Livewire\Component;
use App\Models\Patient;

new class extends Component {
    public function render()
    {
        $patients = Patient::orderBy('name', 'asc')->paginate(25);
        return $this->view([
            'patients' => $patients,
        ]);
    }
};
?>

<div>
    <x-header header="Daftar Pasien" description="List pasien yang sudah terdaftar di sistem" />
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama / NIK</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status SATUSEHAT</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($patients as $patient)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $patient->name }}</div>
                        <div class="text-sm text-gray-500">{{ $patient->nik }}</div>
                    </td>
                    <td class="px-6 py-4">
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
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <button class="text-blue-600 hover:text-blue-900">Detail</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
