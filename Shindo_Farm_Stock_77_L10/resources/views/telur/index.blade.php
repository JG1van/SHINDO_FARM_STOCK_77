@extends('layouts.app')

@section('title', 'Data Telur - SHINDO FARM 77')

@section('content')
    <div class="page-header-neo">
        <h2 class="fw-bold mb-0">Data Telur</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-neo btn-neo-secondary px-3 py-2" id="btnVoiceInput" type="button">🎤 Input Suara</button>
            <button class="btn btn-neo btn-neo-primary px-4 py-2" onclick="bukaModalTambah()">+ Tambah Data Telur</button>
        </div>
    </div>

    <!-- Filter Bulan & Tahun -->
    <div class="d-flex gap-2 mb-3">
        <select id="filterBulan" class="form-select form-control-neo" style="max-width:160px">
            @foreach (range(1, 12) as $b)
                <option value="{{ $b }}" {{ $b == $bulan ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}
                </option>
            @endforeach
        </select>
        <select id="filterTahun" class="form-select form-control-neo" style="max-width:120px">
            @foreach (range(now()->year, now()->year - 3) as $t)
                <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-responsive">
        <table class="table table-neo align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kandang</th>
                    <th>Jumlah Butir</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelTelur">
                <tr>
                    <td colspan="4" class="text-center py-4">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade modal-neo" id="modalTelur" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formTelur">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalTelurTitle">Tambah Data Telur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="telur_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kandang</label>
                            <select class="form-select form-control-neo" id="kandang_id" required>
                                <option value="">-- Pilih Kandang --</option>
                                @foreach ($kandangs as $kandang)
                                    <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" class="form-control form-control-neo" id="tanggal" max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Butir</label>
                            <input type="number" class="form-control form-control-neo" id="jumlah_butir" min="0"
                                required>
                        </div>
                        <div id="errorTelur" class="text-danger small"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-neo btn-neo-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade modal-neo" id="modalHapusTelur" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hapus Data Telur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus data telur tanggal
                        <strong id="tanggalTelurHapus"></strong>? Data ini tidak bisa dikembalikan.
                    </p>
                    <div id="errorHapusTelur" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-neo btn-neo-danger" id="btnKonfirmasiHapusTelur">Ya,
                        Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Voice Input (guided step-by-step) -->
    <div class="modal fade modal-neo" id="modalVoiceTelur" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Input Suara</h5>
                    <button type="button" class="btn-close" id="btnBatalVoice"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div style="font-size:2.5rem;" id="voiceIcon">🎤</div>
                    <p class="fw-bold fs-5 mt-3 mb-1" id="voiceStepText">Bersiap...</p>
                    <p class="text-muted mb-0" id="voiceStatusText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neo btn-neo-secondary w-100" id="btnBatalVoice2">Batal</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const telurBaseUrl = "{{ route('telur.index') }}";
        const modalTelur = new bootstrap.Modal(document.getElementById('modalTelur'));
        const modalHapusTelur = new bootstrap.Modal(document.getElementById('modalHapusTelur'));
        let idTelurDihapus = null;

        function muatTelur() {
            const bulan = document.getElementById('filterBulan').value;
            const tahun = document.getElementById('filterTahun').value;

            fetch(`${telurBaseUrl}?bulan=${bulan}&tahun=${tahun}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const tbody = document.getElementById('tabelTelur');
                    if (!res.data.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="4" class="text-center py-4">Belum ada data telur bulan ini</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(t => `
        <tr>
          <td>${t.tanggal}</td>
          <td>${t.kandang ? t.kandang.nama : '-'}</td>
          <td>${t.jumlah_butir}</td>
          <td class="text-end">
            <button class="btn btn-neo btn-neo-secondary btn-neo-sm" onclick="bukaModalEdit(${t.id})">Edit</button>
            <button class="btn btn-neo btn-neo-danger btn-neo-sm" onclick="bukaModalHapus(${t.id}, '${t.tanggal}')">Hapus</button>
          </td>
        </tr>
      `).join('');
                });
        }

        function bukaModalTambah() {
            document.getElementById('formTelur').reset();
            document.getElementById('telur_id').value = '';
            document.getElementById('tanggal').value = new Date().toLocaleDateString('sv-SE');
            document.getElementById('modalTelurTitle').textContent = 'Tambah Data Telur';
            document.getElementById('errorTelur').textContent = '';
            document.getElementById('errorTelur').className = 'text-danger small';
            modalTelur.show();
        }

        function bukaModalEdit(id) {
            fetch(`${telurBaseUrl}/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const t = res.data;
                    document.getElementById('telur_id').value = t.id;
                    document.getElementById('kandang_id').value = t.kandang_id;
                    document.getElementById('tanggal').value = t.tanggal;
                    document.getElementById('jumlah_butir').value = t.jumlah_butir;
                    document.getElementById('modalTelurTitle').textContent = 'Edit Data Telur';
                    document.getElementById('errorTelur').textContent = '';
                    modalTelur.show();
                });
        }

        document.getElementById('formTelur').addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Menyimpan...';

            const id = document.getElementById('telur_id').value;
            const url = id ? `${telurBaseUrl}/${id}` : telurBaseUrl;
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        kandang_id: document.getElementById('kandang_id').value,
                        tanggal: document.getElementById('tanggal').value,
                        jumlah_butir: document.getElementById('jumlah_butir').value
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalTelur.hide();
                        muatTelur();
                    } else {
                        document.getElementById('errorTelur').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Simpan';
                });
        });

        // Buka modal konfirmasi hapus (menggantikan confirm() bawaan browser)
        function bukaModalHapus(id, tanggal) {
            idTelurDihapus = id;
            document.getElementById('tanggalTelurHapus').textContent = tanggal;
            document.getElementById('errorHapusTelur').textContent = '';
            modalHapusTelur.show();
        }

        // Jalan saat tombol "Ya, Hapus" di dalam modal diklik
        document.getElementById('btnKonfirmasiHapusTelur').addEventListener('click', function() {
            if (!idTelurDihapus) return;

            const btnHapus = document.getElementById('btnKonfirmasiHapusTelur');
            btnHapus.disabled = true;
            btnHapus.textContent = 'Menghapus...';

            fetch(`${telurBaseUrl}/${idTelurDihapus}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        modalHapusTelur.hide();
                        idTelurDihapus = null;
                        muatTelur();
                    } else {
                        document.getElementById('errorHapusTelur').textContent = res.message;
                    }
                })
                .finally(() => {
                    btnHapus.disabled = false;
                    btnHapus.textContent = 'Ya, Hapus';
                });
        });

        // Filter berubah -> reload data
        document.getElementById('filterBulan').addEventListener('change', muatTelur);
        document.getElementById('filterTahun').addEventListener('change', muatTelur);

        // ===== VOICE INPUT (guided step-by-step, terbukti berhasil) =====
        const btnVoiceInput = document.getElementById('btnVoiceInput');
        const modalVoiceTelur = new bootstrap.Modal(document.getElementById('modalVoiceTelur'));
        const voiceStepText = document.getElementById('voiceStepText');
        const voiceStatusText = document.getElementById('voiceStatusText');
        const voiceIcon = document.getElementById('voiceIcon');

        let voiceStep = null;
        let voiceKandangHasil = null;
        let voiceJumlahHasil = null;
        let voiceDibatalkan = false;
        let voiceRetry = 0;
        const VOICE_MAX_RETRY = 3;

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let recognition = null;

        if (SpeechRecognition) {
            recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.continuous = false;
            recognition.interimResults = false;

            recognition.addEventListener('result', function(e) {
                if (voiceDibatalkan) return;
                const ucapan = e.results[0][0].transcript.toLowerCase();

                if (voiceStep === 'kandang') {
                    const opsiKandang = Array.from(document.getElementById('kandang_id').options);
                    const cocok = opsiKandang.find(opt => opt.value && ucapan.includes(opt.text.toLowerCase()));

                    if (cocok) {
                        voiceKandangHasil = cocok;
                        voiceRetry = 0;
                        voiceStatusText.textContent = 'Kandang terdeteksi: ' + cocok.text;
                        setTimeout(tanyaJumlah, 800);
                    } else {
                        gagalKenali('Kandang tidak dikenali dari: "' + ucapan + '"', tanyaKandang);
                    }
                } else if (voiceStep === 'jumlah') {
                    const cocokAngka = ucapan.match(/\d+/);
                    if (cocokAngka) {
                        voiceJumlahHasil = cocokAngka[0];
                        voiceRetry = 0;
                        voiceStatusText.textContent = 'Jumlah terdeteksi: ' + voiceJumlahHasil;
                        setTimeout(selesaiVoiceFlow, 800);
                    } else {
                        gagalKenali('Jumlah tidak dikenali dari: "' + ucapan + '"', tanyaJumlah);
                    }
                }
            });

            recognition.addEventListener('error', function(e) {
                if (voiceDibatalkan) return;
                gagalKenali('Gagal menangkap suara (' + e.error + ').', function() {
                    if (voiceStep === 'kandang') tanyaKandang();
                    else if (voiceStep === 'jumlah') tanyaJumlah();
                });
            });
        }

        function gagalKenali(pesan, ulangiFn) {
            voiceRetry++;
            voiceIcon.textContent = '🎤';
            voiceStatusText.textContent = pesan;

            if (voiceRetry >= VOICE_MAX_RETRY) {
                ucapkan('Maaf, gagal mengenali suara setelah beberapa kali percobaan. Silakan isi form secara manual.',
                    function() {
                        batalkanVoiceFlow();
                    });
                return;
            }

            ucapkan('Maaf, tidak dikenali. Coba lagi.', function() {
                setTimeout(ulangiFn, 300);
            });
        }

        function ucapkan(teks, callback) {
            if (!window.speechSynthesis) { if (callback) callback(); return; }
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(teks);
            utter.lang = 'id-ID';
            utter.onend = function() { if (callback && !voiceDibatalkan) callback(); };
            window.speechSynthesis.speak(utter);
        }

        function mulaiVoiceFlow() {
            if (!recognition) {
                alert('Browser tidak mendukung input suara. Gunakan Chrome atau Edge.');
                return;
            }
            voiceDibatalkan = false;
            voiceKandangHasil = null;
            voiceJumlahHasil = null;
            voiceRetry = 0;
            modalVoiceTelur.show();
            tanyaKandang();
        }

        function tanyaKandang() {
            if (voiceDibatalkan) return;
            voiceStep = 'kandang';
            voiceStepText.textContent = 'Sebutkan nama kandang';
            voiceStatusText.textContent = 'Menyiapkan...';
            voiceIcon.textContent = '🔊';
            // Jeda sebelum listen dimulai, supaya tidak bentrok dengan dialog izin mikrofon
            ucapkan('Sebutkan nama kandang', function() {
                if (voiceDibatalkan) return;
                voiceIcon.textContent = '🎤';
                voiceIcon.classList.add('voice-pulse');
                voiceStatusText.textContent = 'Mendengarkan...';
                try {
                    recognition.start();
                } catch (err) {
                    // Recognition masih dalam sesi sebelumnya, coba abort lalu mulai ulang
                    recognition.abort();
                    setTimeout(() => recognition.start(), 300);
                }
            });
        }

        function tanyaJumlah() {
            if (voiceDibatalkan) return;
            voiceStep = 'jumlah';
            voiceStepText.textContent = 'Sebutkan jumlah telur';
            voiceStatusText.textContent = 'Menyiapkan...';
            voiceIcon.textContent = '🔊';
            ucapkan('Sebutkan jumlah telur', function() {
                if (voiceDibatalkan) return;
                voiceIcon.textContent = '🎤';
                voiceIcon.classList.add('voice-pulse');
                voiceStatusText.textContent = 'Mendengarkan...';
                try {
                    recognition.start();
                } catch (err) {
                    recognition.abort();
                    setTimeout(() => recognition.start(), 300);
                }
            });
        }

        function selesaiVoiceFlow() {
            if (voiceDibatalkan) return;
            voiceIcon.classList.remove('voice-pulse');
            voiceStepText.textContent = 'Selesai';
            voiceStatusText.textContent = 'Mengisi form...';
            ucapkan('Data diterima', function() {
                modalVoiceTelur.hide();
                bukaModalTambah();

                if (voiceKandangHasil) {
                    document.getElementById('kandang_id').value = voiceKandangHasil.value;
                }
                if (voiceJumlahHasil) {
                    document.getElementById('jumlah_butir').value = voiceJumlahHasil;
                }

                document.getElementById('errorTelur').className = 'text-success small';
                document.getElementById('errorTelur').textContent =
                    `Dikenali: ${voiceKandangHasil ? voiceKandangHasil.text : '-'}, jumlah ${voiceJumlahHasil || '-'}. Silakan cek dan klik Simpan.`;

                // Fokus otomatis ke tombol Simpan supaya tinggal tekan Enter/Space
                document.querySelector('#formTelur button[type="submit"]').focus();
            });
        }

        function batalkanVoiceFlow() {
            voiceDibatalkan = true;
            voiceIcon.classList.remove('voice-pulse');
            if (recognition) recognition.abort();
            if (window.speechSynthesis) window.speechSynthesis.cancel();
            modalVoiceTelur.hide();
        }

        if (btnVoiceInput) {
            btnVoiceInput.addEventListener('click', mulaiVoiceFlow);
        }
        document.getElementById('btnBatalVoice').addEventListener('click', batalkanVoiceFlow);
        document.getElementById('btnBatalVoice2').addEventListener('click', batalkanVoiceFlow);

        muatTelur();
    </script>

    <style>
        #voiceIcon.voice-pulse {
            animation: voicePulse 1s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes voicePulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.25); opacity: 0.6; }
        }
    </style>
@endsection