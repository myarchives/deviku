<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrigerBackup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        DB::unprepared('CREATE TRIGGER update_backups AFTER UPDATE ON `contents` FOR EACH ROW
        BEGIN
            INSERT INTO trashes SET  idcopy=old.f720p,token= (Select tokenDriveAdmin from settings where id="1");
            DELETE FROM `backups` WHERE backups.f720p = old.f720p;
        END');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        DB::unprepared('DROP TRIGGER `update_backups');
    }
}
