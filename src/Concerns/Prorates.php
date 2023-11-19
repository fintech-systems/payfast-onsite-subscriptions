<?php

namespace FintechSystems\Payfast\Concerns;

trait Prorates
{
    /**
     * Indicates if the plan change should be prorated.
     *
     * @var bool
     */
    protected bool $prorate = true;

    /**
     * Indicate that the plan change should not be prorated.
     *
     * @return $this
     */
    public function noProrate(): static
    {
        $this->prorate = false;

        return $this;
    }

    /**
     * Indicate that the plan change should be prorated.
     *
     * @return $this
     */
    public function prorate(): static
    {
        $this->prorate = true;

        return $this;
    }

    /**
     * Set the prorating behavior for the plan change.
     *
     * @param bool $prorate
     * @return $this
     */
    public function setProration(bool $prorate = true): static
    {
        $this->prorate = $prorate;

        return $this;
    }
}
