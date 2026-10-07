<?php

use App\Models\PengeluaranHeader;
use App\Models\PengeluaranRinci;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatRincianPengeluaran(string $tanggal, array $nominal): array
{
    $header = PengeluaranHeader::create([
        'tanggal_pengeluaran' => $tanggal,
        'kegiatan' => 'Kegiatan warga',
        'jenis_transaksi' => 'RUKEM',
        'total_nominal' => array_sum($nominal),
    ]);

    $rincian = collect($nominal)->map(fn (int $nilai) => PengeluaranRinci::create([
        'pengeluaran_header_id' => $header->id,
        'harga_satuan' => $nilai,
        'jumlah' => 1,
        'nominal' => $nilai,
    ]));

    return [$header, $rincian];
}

test('rincian pengeluaran bulan berjalan dapat dihapus dan total diperbarui', function () {
    [$header, $rincian] = buatRincianPengeluaran(now()->toDateString(), [10000, 25000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->deleteJson("/api/v1/pengeluaran/rincian/{$rincian->first()->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertDatabaseMissing('pengeluaran_rincis', ['id' => $rincian->first()->id]);
    $this->assertDatabaseHas('pengeluaran_headers', [
        'id' => $header->id,
        'total_nominal' => 25000,
    ]);
});

test('rincian pengeluaran di luar bulan berjalan tidak dapat dihapus', function () {
    [$header, $rincian] = buatRincianPengeluaran(now()->subMonth()->toDateString(), [10000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->deleteJson("/api/v1/pengeluaran/rincian/{$rincian->first()->id}")
        ->assertUnprocessable()
        ->assertJsonPath('status', false);

    $this->assertDatabaseHas('pengeluaran_headers', ['id' => $header->id]);
    $this->assertDatabaseHas('pengeluaran_rincis', ['id' => $rincian->first()->id]);
});

test('menghapus rincian terakhir juga menghapus header transaksi', function () {
    [$header, $rincian] = buatRincianPengeluaran(now()->toDateString(), [10000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->deleteJson("/api/v1/pengeluaran/rincian/{$rincian->first()->id}")
        ->assertOk();

    $this->assertDatabaseMissing('pengeluaran_headers', ['id' => $header->id]);
    $this->assertDatabaseMissing('pengeluaran_rincis', ['id' => $rincian->first()->id]);
});

test('header pengeluaran hanya dapat mengubah kegiatan', function () {
    [$header] = buatRincianPengeluaran(now()->toDateString(), [10000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->patchJson("/api/v1/pengeluaran/{$header->id}/header", [
            'kegiatan' => 'Kegiatan yang diperbarui',
        ])
        ->assertOk()
        ->assertJsonPath('data.kegiatan', 'Kegiatan yang diperbarui');

    $this->assertDatabaseHas('pengeluaran_headers', [
        'id' => $header->id,
        'kegiatan' => 'Kegiatan yang diperbarui',
        'jenis_transaksi' => 'RUKEM',
        'total_nominal' => 10000,
    ]);
});

test('header pengeluaran menolak perubahan jenis transaksi dan nominal', function () {
    [$header] = buatRincianPengeluaran(now()->toDateString(), [10000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->patchJson("/api/v1/pengeluaran/{$header->id}/header", [
            'kegiatan' => 'Kegiatan warga',
            'jenis_transaksi' => 'KOTAK_MASJID',
            'total_nominal' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['jenis_transaksi', 'total_nominal']);

    $this->assertDatabaseHas('pengeluaran_headers', [
        'id' => $header->id,
        'jenis_transaksi' => 'RUKEM',
        'total_nominal' => 10000,
    ]);
});

test('header pengeluaran bulan berjalan dapat dihapus beserta rinciannya', function () {
    [$header, $rincian] = buatRincianPengeluaran(now()->toDateString(), [10000, 25000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->deleteJson("/api/v1/pengeluaran/header/{$header->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertDatabaseMissing('pengeluaran_headers', ['id' => $header->id]);
    $this->assertDatabaseMissing('pengeluaran_rincis', ['id' => $rincian->first()->id]);
    $this->assertDatabaseMissing('pengeluaran_rincis', ['id' => $rincian->last()->id]);
});

test('header pengeluaran di luar bulan berjalan tidak dapat dihapus', function () {
    [$header, $rincian] = buatRincianPengeluaran(now()->subMonth()->toDateString(), [10000]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->deleteJson("/api/v1/pengeluaran/header/{$header->id}")
        ->assertUnprocessable()
        ->assertJsonPath('status', false);

    $this->assertDatabaseHas('pengeluaran_headers', ['id' => $header->id]);
    $this->assertDatabaseHas('pengeluaran_rincis', ['id' => $rincian->first()->id]);
});
