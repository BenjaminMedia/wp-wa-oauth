<?php

/**
 * Returns an instance of the bp-wa-oauth plugin
 *
 * @return \Bonnier\WP\WaOauth\Plugin|null
 */
function bp_wa_oauth()
{
    return isset($GLOBALS['bp_wa_oauth']) ? $GLOBALS['bp_wa_oauth'] : null;
}