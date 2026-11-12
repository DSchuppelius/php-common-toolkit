<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecipientType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Mail;

/**
 * Art eines Nachrichtenempfängers — wie die Kopfzeilen To, Cc und Bcc.
 */
enum RecipientType: string {
    case To = 'to';
    case Cc = 'cc';
    case Bcc = 'bcc';
}
