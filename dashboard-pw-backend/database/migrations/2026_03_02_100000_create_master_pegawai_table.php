<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18)->unique();
            $table->string('niplama', 9)->nullable();
            $table->string('nipawal', 18)->nullable();
            $table->string('nokarpeg', 30)->nullable();
            $table->string('nama', 50);
            $table->string('glrdepan', 30)->nullable();
            $table->string('glrbelakan', 30)->nullable();
            $table->string('kdjenkel', 1)->nullable();   // 1=Laki, 2=Perempuan
            $table->string('tempatlhr', 41)->nullable();
            $table->string('tgllhr', 10)->nullable();    // YYYY-MM-DD string
            $table->string('agama', 1)->nullable();      // 1=Islam,2=Kristen,dst
            $table->string('pendidikan', 20)->nullable();
            $table->string('kdstawin', 1)->nullable();   // status kawin
            $table->string('jistri', 1)->nullable();     // jumlah istri
            $table->string('janak', 1)->nullable();      // jumlah anak
            $table->string('kdpangkat', 2)->nullable();  // golongan/pangkat
            $table->string('blgolt', 2)->nullable();
            $table->string('mkgolt', 2)->nullable();
            $table->string('masker', 2)->nullable();
            $table->string('kdskpd', 3)->nullable();
            $table->string('kdsatker', 20)->nullable();
            $table->string('kdfungsi', 5)->nullable();
            $table->string('kdeselon', 2)->nullable();
            $table->string('kdstruk', 1)->nullable();
            $table->integer('gapok')->nullable();
            $table->string('prsngapok', 6)->nullable();
            $table->integer('tjfungsi')->nullable();
            $table->integer('tjeselon')->nullable();
            $table->integer('tjkhusus')->default(0);
            $table->integer('tjlangka')->default(0);
            $table->integer('tjterpenci')->default(0);
            $table->integer('tjtkd')->default(0);
            $table->integer('tjguru')->default(0);
            $table->integer('tjstruk')->default(0);
            $table->integer('taperum')->default(0);
            $table->string('kdberas', 1)->nullable();
            $table->string('kdlangka', 1)->nullable();
            $table->string('kdterpenci', 3)->nullable();
            $table->string('kdtjkhusus', 5)->nullable();
            $table->string('kdtkd', 1)->nullable();
            $table->string('kdguru', 3)->nullable();
            $table->string('kdhitung', 2)->nullable();
            $table->integer('kd_jns_peg')->nullable(); // 1=PNS, 2=PPPK
            $table->string('tmtcapeg', 10)->nullable();
            $table->string('tmtkgb', 10)->nullable();
            $table->string('tmtkgbyad', 10)->nullable();
            $table->string('tmtberlaku', 10)->nullable();
            $table->string('tmtskmt', 10)->nullable();
            $table->string('tmtstop', 10)->nullable();
            $table->string('tmttabel', 10)->nullable();
            $table->string('bup', 2)->nullable();        // batas usia pensiun
            $table->string('kdstapeg', 2)->nullable();
            $table->string('kdirdhata', 2)->nullable();
            $table->integer('pirdhata')->default(0);
            $table->string('kdkorpri', 2)->nullable();
            $table->integer('pkorpri')->default(0);
            $table->string('kdkoperasi', 2)->nullable();
            $table->integer('pkoperasi')->default(0);
            $table->integer('psewa')->default(0);
            $table->string('kdcabtaspe', 3)->nullable();
            $table->string('kodebyr', 4)->nullable();
            $table->string('kdssbp', 3)->nullable();
            $table->string('noktp', 30)->nullable();
            $table->string('npwp', 25)->nullable();
            $table->string('npwpz', 20)->nullable();
            $table->string('norek', 40)->nullable();
            $table->string('nohandphon', 25)->nullable();
            $table->string('notelp', 40)->nullable();
            $table->string('nodosir', 10)->nullable();
            $table->string('nosks', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kddati1', 2)->nullable();
            $table->string('kddati2', 2)->nullable();
            $table->string('kddati3', 2)->nullable();
            $table->string('kddati4', 3)->nullable();
            $table->string('kddati1_al', 4)->nullable();
            $table->string('kddati2_al', 4)->nullable();
            $table->string('kdjnstrans', 1)->nullable();
            $table->string('induk_bank', 4)->nullable();
            $table->integer('jnsguru')->default(0);
            $table->integer('zakat_dg')->default(0);
            $table->integer('kd_infaq')->default(0);
            $table->text('catatan')->nullable();
            $table->string('inputer', 20)->nullable();
            $table->string('updstamp', 23)->nullable();

            // Upload tracking
            $table->string('upload_batch', 30)->nullable(); // e.g. "2026-3"
            $table->timestamps();

            $table->index('kdskpd');
            $table->index('kdpangkat');
            $table->index('kd_jns_peg');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_pegawai');
    }
};
