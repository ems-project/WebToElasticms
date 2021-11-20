<?php

declare(strict_types=1);

namespace App\Update;

use App\Config\ConfigManager;
use App\Extract\ExtractedData;
use EMS\CommonBundle\Common\CoreApi\CoreApi;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiExceptionInterface;
use Psr\Log\LoggerInterface;

class UpdateManager
{
    private CoreApi $coreApi;
    private ConfigManager $configManager;
    private LoggerInterface $logger;

    public function __construct(CoreApi $coreApi, ConfigManager $configManager, LoggerInterface $logger)
    {
        $this->coreApi = $coreApi;
        $this->configManager = $configManager;
        $this->logger = $logger;
    }

    public function update(ExtractedData $extractedData, bool $force): void
    {
        $ouuid = $extractedData->getDocument()->getOuuid();
        $data = $extractedData->getData();
        $type = $this->configManager->getType($extractedData->getDocument()->getType());
        $typeManager = $this->coreApi->data($extractedData->getDocument()->getType());
        if (null === $ouuid || !$typeManager->head($ouuid)) {
            $data = \array_merge_recursive($type->getDefaultData(), $data);
            $draft = $typeManager->create($data, $ouuid);
            try {
                $ouuid = $typeManager->finalize($draft->getRevisionId());
            } catch (CoreApiExceptionInterface $e) {
                $typeManager->discard($draft->getRevisionId());
            }
            $extractedData->getDocument()->setOuuid($ouuid);
        } else {
            $hash = $data[$this->configManager->getHashResourcesField()];
            if (!$force && null !== $hash && $hash === $typeManager->get($ouuid)->getRawData()[$this->configManager->getHashResourcesField()] ?? null) {
                return;
            }
            $typeManager->save($ouuid, $data);
        }
    }
}
