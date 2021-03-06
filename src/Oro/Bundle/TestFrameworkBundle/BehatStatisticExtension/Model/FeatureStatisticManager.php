<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model;

use Behat\Gherkin\Node\FeatureNode;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\CriteriaArrayCollection;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;

/**
 * Entry point to work with statistic data.
 */
class FeatureStatisticManager
{
    /** @var StatisticRepository */
    private $featureRepository;

    /** @var FeaturePathLocator */
    private $featurePathLocator;

    /** @var CriteriaArrayCollection */
    private $criteria;

    /**
     * @param StatisticRepository $featureRepository
     * @param FeaturePathLocator $featurePathLocator
     * @param CriteriaArrayCollection $criteria
     */
    public function __construct(
        StatisticRepository $featureRepository,
        FeaturePathLocator $featurePathLocator,
        CriteriaArrayCollection $criteria
    ) {
        $this->featureRepository = $featureRepository;
        $this->featurePathLocator = $featurePathLocator;
        $this->criteria = clone $criteria;
    }

    /**
     * @param FeatureNode $feature
     * @param float $time
     */
    public function addStatistic(FeatureNode $feature, float $time): void
    {
        $featurePath = $this->featurePathLocator->getRelativePath($feature->getFile());

        $stat = new FeatureStatistic();
        $stat->setPath($featurePath)
            ->setDuration($time)
            ->setGitBranch($this->getGitBranch())
            ->setGitTarget($this->getGitTarget())
            ->setBuildId($this->getBuildId());

        $this->featureRepository->add($stat);
    }

    /**
     * @return array
     */
    public function getTested(): array
    {
        $buildId = $this->getBuildId();
        $gitTarget = $this->getGitTarget();

        if (!$buildId || !$gitTarget) {
            return [];
        }

        $criteria = ['build_id' => $buildId, 'git_target' => $gitTarget, 'git_branch' => $this->getGitBranch()];

        return array_map(
            function (FeatureStatistic $statistic) {
                return $statistic->getPath();
            },
            $this->featureRepository->findBy($criteria)
        );
    }

    public function saveStatistics()
    {
        $this->featureRepository->flush();
    }

    public function cleanOldStatistics()
    {
        if ($this->getGitTarget() === 'master' && $this->getGitBranch() === null) {
            $this->featureRepository->removeOldStatistics($this->getStatisticsLifetime());
        }
    }

    /**
     * @return null|string
     */
    private function getBuildId(): ?string
    {
        return $this->criteria->get('build_id');
    }

    /**
     * @return null|string
     */
    private function getGitBranch(): ?string
    {
        return $this->criteria->get('branch_name') ?: $this->criteria->get('single_branch_name');
    }

    /**
     * @return null|string
     */
    private function getGitTarget(): ?string
    {
        return $this->criteria->get('target_branch');
    }

    /**
     * @return int
     */
    private function getStatisticsLifetime(): int
    {
        $lifetime = $this->criteria->get('lifetime') ?: 30;

        return $lifetime * 86400; //one month by default
    }
}
