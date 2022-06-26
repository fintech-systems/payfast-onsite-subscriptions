<?php

namespace FintechSystems\PayFast;

use FintechSystems\PayFast\Concerns\ManagesCustomer;
use FintechSystems\PayFast\Concerns\ManagesReceipts;
use FintechSystems\PayFast\Concerns\ManagesSubscriptions;
use FintechSystems\PayFast\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesSubscriptions;
    use ManagesReceipts;
    use PerformsCharges;

    /**
     * Get the default PayFast API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function payFastOptions(array $options = [])
    {
        return Cashier::payFastOptions($options);
    }
}
