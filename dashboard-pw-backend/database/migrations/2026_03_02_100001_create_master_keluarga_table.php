<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_keluarga', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18)->index();
            $table->string('nmkel', 50);                  // nama keluarga
            $table->string('kdhubkel', 2)->nullable();    // hub. keluarga: 10=istri,01=anak,dst
            $table->string('kdjenkel', 1)->nullable();    // jenis kelamin
            $table->string('tgllhr', 10)->nullable();     // tanggal lahir
            $table->string('tglnikah', 10)->nullable();
            $table->string('tglcerai', 10)->nullable();
            $table->string('tglwafat', 10)->nullable();
            $table->string('tglsks', 10)->nullable();
            $table->string('tatsks', 10)->nullable();
            $table->string('glrdepan', 20)->nullable();
            $table->string('glrbelakan', 20)->nullable();
            $table->string('kdtunjang', 1)->nullable();   // 1=menerima tunjangan
            $table->integer('kdstawin')->nullable();      // status kawin
            $table->string('nipsuamiis', 18)->nullable(); // NIP suami/istri (jika PNS)
            $table->string('pekerjaan', 60)->nullable();
            $table->string('nosrtnikah', 4)->nullable();
            $table->string('nosrtcerai', 4)->nullable();
            $table->string('nosrtwafat', 4)->nullable();
            $table->string('noaktalahi', 4)->nullable();
            $table->string('nosks', 100)->nullable();
            $table->string('kddati1', 2)->nullable();
            $table->string('kddati2', 2)->nullable();
            $table->string('inputer', 27)->nullable();
            $table->string('updstamp', 23)->nullable();

            // Upload tracking
            $table->string('upload_batch', 30)->nullable();
            $table->timestamps();

            $table->index('kdhubkel');
            $table->index('kdtunjang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_keluarga');
    }
};
