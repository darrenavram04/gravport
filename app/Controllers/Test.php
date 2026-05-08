<?php

namespace App\Controllers;

class Test extends BaseController
{
    public function pgsql()
    {
        echo 'extension_loaded("pgsql") = ';
        var_dump(extension_loaded('pgsql'));
    }
}
