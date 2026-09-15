<?php

namespace App\Models;

use App\Design\Standard;
use Database\Factories\SiteReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One thing a site said about itself.
 *
 * Counts by rule, never the findings themselves. The tool does not need to
 * know which line of somebody's view is off, and storing that would put other
 * people's source in this database for no gain.
 *
 * @property int $site_id
 * @property string|null $standard_version
 * @property string|null $package_version
 * @property string|null $design_slug
 * @property array<string,int> $findings
 * @property int $total_findings
 * @property int $files_with_findings
 * @property int $files_checked
 * @property Carbon $reported_at
 */
class SiteReport extends Model
{
    /** @use HasFactory<SiteReportFactory> */
    use HasFactory;

    protected $fillable = [
        'standard_version', 'package_version', 'design_slug',
        'findings', 'total_findings', 'files_with_findings', 'files_checked',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'reported_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Whether this site was on the standard as it read at the time of writing.
     *
     * A site that has never been stamped is behind rather than unknown: the
     * stamp arrived with the standard itself, so no stamp means it predates it.
     */
    public function isCurrent(): bool
    {
        return $this->standard_version !== null && $this->standard_version === Standard::version();
    }

    /**
     * Whether a design has ever been applied to this app.
     *
     * Both stamps come out of design/, so an app that has never run
     * design:apply reports neither. That is a different thing from an app on
     * the standard whose copy predates stamping, and the board showed the
     * second when it meant the first: a repo added to the board before anyone
     * set it up read as drift rather than as not started.
     */
    public function hasDesign(): bool
    {
        return $this->standard_version !== null || $this->design_slug !== null;
    }
}
