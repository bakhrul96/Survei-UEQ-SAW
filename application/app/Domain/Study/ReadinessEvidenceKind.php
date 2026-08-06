<?php

namespace App\Domain\Study;

enum ReadinessEvidenceKind: string
{
    case Https = 'https';
    case BackupRestore = 'backup_restore';
    case SubmitTest = 'submit_test';
}
