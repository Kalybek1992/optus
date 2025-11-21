<?php

namespace Source\Project\Models;

use Source\Base\Model\ModelPdo;

/**
 * @property int|null $is_banned
 * @property mixed|string[] $markets
 */
class UploadedDocuments extends ModelPdo
{

    public static ?string $static_table = 'uploaded_documents';

}

