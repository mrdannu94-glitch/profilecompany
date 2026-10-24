<?php

namespace App\Exceptions\Cms;

use RuntimeException;

/**
 * Pelanggaran alur kerja CMS.
 *
 * Dilempar ketika transisi status tidak diizinkan, misalnya memublikasikan
 * revisi yang belum disetujui atau menyunting revisi yang sedang ditinjau.
 */
class ContentWorkflowException extends RuntimeException {}
