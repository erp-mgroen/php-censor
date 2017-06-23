<?php

namespace PHPCensor\Model\Build;

use PHPCensor\Model\Build;
use PHPCensor\Builder;

class CrmBuild extends RemoteGitBuild
{
    /**
     * Get link to commit from another source (i.e. Github)
     */
    public function getCommitLink()
    {
        $domain = $this->getProject()->getAccessInformation("domain");
        return 'http://' . $domain . '/' . $this->getProject()->getReference() . '/commit/' . $this->getCommitId();
    }

    /**
     * Get link to branch from another source (i.e. Github)
     */
    public function getBranchLink()
    {
        $domain = $this->getProject()->getAccessInformation("domain");
        return 'http://' . $domain . '/' . $this->getProject()->getReference() . '/tree/' . $this->getBranch();
    }

    /**
     * Get link to specific file (and line) in a the repo's branch
     */
    public function getFileLinkTemplate()
    {
        return sprintf(
            'http://%s/%s/blob/%s/{FILE}#L{LINE}',
            $this->getProject()->getAccessInformation("domain"),
            $this->getProject()->getReference(),
            $this->getCommitId()
        );
    }

    /**
     * Get the URL to be used to clone this remote repository.
     */
    protected function getCloneUrl()
    {
        $key = trim($this->getProject()->getSshPrivateKey());

        if (!empty($key)) {
            $user = $this->getProject()->getAccessInformation("user");
            $domain = $this->getProject()->getAccessInformation("domain");
            $port = $this->getProject()->getAccessInformation('port');

            $url = $user . '@' . $domain . ':';

            if (!empty($port)) {
                $url .= $port . '/';
            }

            $url .= $this->getProject()->getReference() . '.git';

            return $url;
        }
    }

    protected function cloneBySsh(Builder $builder, $cloneTo)
    {
    }

    public function createWorkingCopy(Builder $builder, $buildPath)
    {
        return $this->handleConfig($builder, $buildPath);
    }

    protected function mergeBranches(Builder $builder, $buildPath)
    {
        return true;
    }

    protected function cloneByHttp(Builder $builder, $cloneTo)
    {
        return true;
    }

    protected function postCloneSetup(Builder $builder, $cloneTo, array $extra = null)
    {
        return true;
    }
}
