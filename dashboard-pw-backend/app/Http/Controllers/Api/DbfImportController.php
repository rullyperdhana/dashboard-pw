<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\DbfReader;
use App\Models\MasterPegawai;
use App\Models\MasterKeluarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DbfImportController extends Controller
{
    public function importMasterPegawai(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'batch' => 'required|string', // e.g. "2026-3"
        ]);

        $file = $request->file('file');
        $batch = $request->input('batch');

        try {
            $reader = new DbfReader($file->getRealPath());
            $records = $reader->readAll();

            DB::beginTransaction();

            // Optional: delete existing batch if requested, or just update by NIP
            // For master data, usually we update if NIP exists

            $count = 0;
            foreach ($records as $record) {
                // Map records to database fields
                $data = $this->mapPegawaiFields($record);
                $data['upload_batch'] = $batch;

                MasterPegawai::updateOrCreate(
                    ['nip' => $data['nip']],
                    $data
                );
                $count++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport $count data Master Pegawai.",
                'total' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DBF Import Master Pegawai Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Gagal mengimport data: " . $e->getMessage()
            ], 500);
        }
    }

    public function importMasterKeluarga(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'batch' => 'required|string',
        ]);

        $file = $request->file('file');
        $batch = $request->input('batch');

        try {
            $reader = new DbfReader($file->getRealPath());
            $records = $reader->readAll();

            DB::beginTransaction();

            // For family data, we might want to clear previous data for the NIPs present in the file
            // to avoid duplicates if re-uploading, or just use a unique key if available.
            // Since we don't have a unique key for each family member in DBF, 
            // we'll use a combination of NIP + NMKEL + TGLLHR as a pseudo-unique key or just append.

            $count = 0;
            foreach ($records as $record) {
                $data = $this->mapKeluargaFields($record);
                $data['upload_batch'] = $batch;

                // Simple implementation: check if exactly same record exists
                $exists = MasterKeluarga::where('nip', $data['nip'])
                    ->where('nmkel', $data['nmkel'])
                    ->where('tgllhr', $data['tgllhr'])
                    ->exists();

                if (!$exists) {
                    MasterKeluarga::create($data);
                    $count++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport $count data Master Keluarga.",
                'total' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DBF Import Master Keluarga Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Gagal mengimport data: " . $e->getMessage()
            ], 500);
        }
    }

    protected function mapPegawaiFields(array $record): array
    {
        return [
            'nip' => $record['nip'] ?? null,
            'niplama' => $record['niplama'] ?? null,
            'nipawal' => $record['nipawal'] ?? null,
            'nokarpeg' => $record['nokarpeg'] ?? null,
            'nama' => $record['nama'] ?? 'Tanpa Nama',
            'glrdepan' => $record['glrdepan'] ?? null,
            'glrbelakan' => $record['glrbelakan'] ?? null,
            'kdjenkel' => $record['kdjenkel'] ?? null,
            'tempatlhr' => $record['tempatlhr'] ?? null,
            'tgllhr' => $record['tgllhr'] ?? null,
            'agama' => $record['agama'] ?? null,
            'pendidikan' => $record['pendidikan'] ?? null,
            'kdstawin' => $record['kdstawin'] ?? null,
            'jistri' => $record['jistri'] ?? null,
            'janak' => $record['janak'] ?? null,
            'kdpangkat' => $record['kdpangkat'] ?? null,
            'blgolt' => $record['blgolt'] ?? null,
            'mkgolt' => $record['mkgolt'] ?? null,
            'masker' => $record['masker'] ?? null,
            'kdskpd' => $record['kdskpd'] ?? null,
            'kdsatker' => $record['kdsatker'] ?? null,
            'kdfungsi' => $record['kdfungsi'] ?? null,
            'kdeselon' => $record['kdeselon'] ?? null,
            'kdstruk' => $record['kdstruk'] ?? null,
            'gapok' => $record['gapok'] ?? 0,
            'prsngapok' => $record['prsngapok'] ?? null,
            'tjfungsi' => $record['tjfungsi'] ?? 0,
            'tjeselon' => $record['tjeselon'] ?? 0,
            'tjkhusus' => $record['tjkhusus'] ?? 0,
            'tjlangka' => $record['tjlangka'] ?? 0,
            'tjterpenci' => $record['tjterpenci'] ?? 0,
            'tjtkd' => $record['tjtkd'] ?? 0,
            'tjguru' => $record['tjguru'] ?? 0,
            'tjstruk' => $record['tjstruk'] ?? 0,
            'taperum' => $record['taperum'] ?? 0,
            'kdberas' => $record['kdberas'] ?? null,
            'kdlangka' => $record['kdlangka'] ?? null,
            'kdterpenci' => $record['kdterpenci'] ?? null,
            'kdtjkhusus' => $record['kdtjkhusus'] ?? null,
            'kdtkd' => $record['kdtkd'] ?? null,
            'kdguru' => $record['kdguru'] ?? null,
            'kdhitung' => $record['kdhitung'] ?? null,
            'kd_jns_peg' => $record['kd_jns_peg'] ?? null,
            'tmtcapeg' => $record['tmtcapeg'] ?? null,
            'tmtkgb' => $record['tmtkgb'] ?? null,
            'tmtkgbyad' => $record['tmtkgbyad'] ?? null,
            'tmtberlaku' => $record['tmtberlaku'] ?? null,
            'tmtskmt' => $record['tmtskmt'] ?? null,
            'tmtstop' => $record['tmtstop'] ?? null,
            'tmttabel' => $record['tmttabel'] ?? null,
            'bup' => $record['bup'] ?? null,
            'kdstapeg' => $record['kdstapeg'] ?? null,
            'kdirdhata' => $record['kdirdhata'] ?? null,
            'pirdhata' => $record['pirdhata'] ?? 0,
            'kdkorpri' => $record['kdkorpri'] ?? null,
            'pkorpri' => $record['pkorpri'] ?? 0,
            'kdkoperasi' => $record['kdkoperasi'] ?? null,
            'pkoperasi' => $record['pkoperasi'] ?? 0,
            'psewa' => $record['psewa'] ?? 0,
            'kdcabtaspe' => $record['kdcabtaspe'] ?? null,
            'kodebyr' => $record['kodebyr'] ?? null,
            'kdssbp' => $record['kdssbp'] ?? null,
            'noktp' => $record['noktp'] ?? null,
            'npwp' => $record['npwp'] ?? null,
            'npwpz' => $record['npwpz'] ?? null,
            'norek' => $record['norek'] ?? null,
            'nohandphon' => $record['nohandphon'] ?? null,
            'notelp' => $record['notelp'] ?? null,
            'nodosir' => $record['nodosir'] ?? null,
            'nosks' => $record['nosks'] ?? null,
            'alamat' => $record['alamat'] ?? null,
            'kddati1' => $record['kddati1'] ?? null,
            'kddati2' => $record['kddati2'] ?? null,
            'kddati3' => $record['kddati3'] ?? null,
            'kddati4' => $record['kddati4'] ?? null,
            'kddati1_al' => $record['kddati1_al'] ?? null,
            'kddati2_al' => $record['kddati2_al'] ?? null,
            'kdjnstrans' => $record['kdjnstrans'] ?? null,
            'induk_bank' => $record['induk_bank'] ?? null,
            'jnsguru' => $record['jnsguru'] ?? 0,
            'zakat_dg' => $record['zakat_dg'] ?? 0,
            'kd_infaq' => $record['kd_infaq'] ?? 0,
            'catatan' => $record['catatan'] ?? null,
            'inputer' => $record['inputer'] ?? null,
            'updstamp' => $record['updstamp'] ?? null,
        ];
    }

    protected function mapKeluargaFields(array $record): array
    {
        return [
            'nip' => $record['nip'] ?? null,
            'nmkel' => $record['nmkel'] ?? 'Tanpa Nama',
            'kdhubkel' => $record['kdhubkel'] ?? null,
            'kdjenkel' => $record['kdjenkel'] ?? null,
            'tgllhr' => $record['tgllhr'] ?? null,
            'tglnikah' => $record['tglnikah'] ?? null,
            'tglcerai' => $record['tglcerai'] ?? null,
            'tglwafat' => $record['tglwafat'] ?? null,
            'tglsks' => $record['tglsks'] ?? null,
            'tatsks' => $record['tatsks'] ?? null,
            'glrdepan' => $record['glrdepan'] ?? null,
            'glrbelakan' => $record['glrbelakan'] ?? null,
            'kdtunjang' => $record['kdtunjang'] ?? null,
            'kdstawin' => $record['kdstawin'] ?? 0,
            'nipsuamiis' => $record['nipsuamiis'] ?? null,
            'pekerjaan' => $record['pekerjaan'] ?? null,
            'nosrtnikah' => $record['nosrtnikah'] ?? null,
            'nosrtcerai' => $record['nosrtcerai'] ?? null,
            'nosrtwafat' => $record['nosrtwafat'] ?? null,
            'noaktalahi' => $record['noaktalahi'] ?? null,
            'nosks' => $record['nosks'] ?? null,
            'kddati1' => $record['kddati1'] ?? null,
            'kddati2' => $record['kddati2'] ?? null,
            'inputer' => $record['inputer'] ?? null,
            'updstamp' => $record['updstamp'] ?? null,
        ];
    }
}
