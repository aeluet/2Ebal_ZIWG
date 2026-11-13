<?php

namespace App\Services\Odoo;

use Exception;

// Odoo-rekin zerbait gaizki doanean jaurtitzen dugun salbuespena.
// Odoo-ren errore-mezua eta HTTP egoera gordetzen ditu (0 = ez dago konexiorik).
class OdooException extends Exception
{
    public function __construct(string $message, public int $egoera = 0)
    {
        parent::__construct($message, $egoera);
    }
}
