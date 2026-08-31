@extends('layouts.app')

@section('title', 'Dashboard - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Rekap Stok Telur</h2>

        <form method="GET" class="d-flex gap-2">
            <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>

            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach (range(now()->year - 1, now()->year + 5) as $y)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-neo align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    @foreach ($kandangs as $k)
                        <th class="text-center">{{ $k->nama }}</th>
                    @endforeach
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fullPivot as $tanggal => $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($tanggal)->format('j') }}</td>
                        @php $rowTotal = 0; @endphp
                        @foreach ($kandangs as $k)
                            @php
                                $val = $row[$k->id] ?? 0;
                                $rowTotal += $val;
                            @endphp
                            <td class="text-center">{{ $val > 0 ? $val : '-' }}</td>
                        @endforeach
                        <td class="text-center fw-bold">{{ $rowTotal > 0 ? $rowTotal : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $kandangs->count() + 2 }}" class="text-center py-4">Belum ada data telur</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($kandangs->count() > 0)
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        @foreach ($kandangs as $k)
                            <td class="text-center">{{ $totalPerKandang[$k->id] }}</td>
                        @endforeach
                        <td class="text-center">{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection
