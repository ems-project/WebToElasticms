<?php

declare(strict_types=1);

namespace App\Update;

use App\Config\ConfigManager;
use App\Extract\ExtractedData;
use EMS\CommonBundle\Common\CoreApi\CoreApi;

class UpdateManager
{
    private CoreApi $coreApi;
    private ConfigManager $configManager;

    public function __construct(CoreApi $coreApi, ConfigManager $configManager)
    {
        $this->coreApi = $coreApi;
        $this->configManager = $configManager;
    }

    public function update(ExtractedData $extractedData): void
    {
        $ouuid = $extractedData->getDocument()->getOuuid();
        $data = $extractedData->getData();
        $type = $this->configManager->getType($extractedData->getDocument()->getType());
        if (null === $ouuid || !$this->coreApi->data($extractedData->getDocument()->getType())->head($ouuid)) {
            $data = \array_merge_recursive($type->getDefaultData(), $data);
        }
    }
}
