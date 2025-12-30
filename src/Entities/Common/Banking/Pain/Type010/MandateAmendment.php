<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateAmendment.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type010;

use CommonToolkit\Entities\Common\Banking\Pain\Mandate\Mandate;

/**
 * Mandate Amendment für pain.010.
 * 
 * Kombiniert das neue Mandat mit den Original-Werten.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type010
 */
final readonly class MandateAmendment {
    public function __construct(
        private Mandate $mandate,
        private AmendmentDetails $amendmentDetails
    ) {
    }

    public static function create(Mandate $mandate, AmendmentDetails $amendmentDetails): self {
        return new self($mandate, $amendmentDetails);
    }

    public function getMandate(): Mandate {
        return $this->mandate;
    }

    public function getAmendmentDetails(): AmendmentDetails {
        return $this->amendmentDetails;
    }
}
