<?php

namespace FintechSystems\Payfast;

use FintechSystems\Payfast\Concerns\ManagesCustomer;
use FintechSystems\Payfast\Concerns\ManagesReceipts;
use FintechSystems\Payfast\Concerns\ManagesSubscriptions;
use FintechSystems\Payfast\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesSubscriptions;
    use ManagesReceipts;
    use PerformsCharges;

    /**
     * Get the default Payfast API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function payfastOptions(array $options = []): array
    {
        return Cashier::payfastOptions($options);
    }
}
