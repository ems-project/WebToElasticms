<?php

declare(strict_types=1);

namespace App\Update;

use App\Config\ConfigManager;
use App\Extract\ExtractedData;
use EMS\CommonBundle\Common\CoreApi\CoreApi;
use EMS\CommonBundle\Common\Standard\Json;
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
        if (!$typeManager->head($ouuid)) {
            $data = \array_merge_recursive($type->getDefaultData(), $data);
            $this->logger->notice(Json::encode($data, true));
            $draft = $typeManager->create($data, $ouuid);
            try {
                $ouuid = $typeManager->finalize($draft->getRevisionId());
                $this->configManager->setLastUpdated($ouuid);
            } catch (CoreApiExceptionInterface $e) {
                $typeManager->discard($draft->getRevisionId());
            }
            $extractedData->getDocument()->setOuuid($ouuid);
        } else {
            $hash = $data[$this->configManager->getHashResourcesField()];
            if (!$force && null !== $hash && $hash === $typeManager->get($ouuid)->getRawData()[$this->configManager->getHashResourcesField()] ?? null) {
                return;
            }
            try {
                $this->logger->notice(Json::encode($data, true));
                $typeManager->save($ouuid, $data);
                $this->configManager->setLastUpdated($ouuid);
            } catch (\Throwable $e) {
                $this->logger->error(\sprintf('Impossible to finalize the document %s with the error %s', $ouuid, $e->getMessage()));
            }
        }
    }
}
