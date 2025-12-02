<?php

namespace App\Models;

/**
 * Compatibility wrapper for legacy `Korban` model usage.
 *
 * This class extends the new `MonitoredSite` model so existing code
 * referencing `Korban` will continue to function. New code should use
 * `MonitoredSite` directly.
 */
class Korban extends MonitoredSite
{
    // intentionally empty: this preserves the old class name as an alias
}
