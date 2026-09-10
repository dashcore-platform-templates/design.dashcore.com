<?php

namespace App\Design\Generation;

use RuntimeException;

/** Anything that stops a generation, with a message fit to show on the form. */
final class GenerationFailed extends RuntimeException {}
