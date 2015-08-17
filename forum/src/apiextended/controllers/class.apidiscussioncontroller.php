<?php

class ApiDiscussionController extends DiscussionController
{
    /**
     * Get a single discussion.
     *
     * @param  int    $foreignId
     * @param  string $page
     *
     * @return void
     */
    public function getByForeignId($foreignId = '', $page = '')
    {
        // Load the discussion record
        $foreignId = ($this->isValidUuid($foreignId)) ? $foreignId : 0;
        if (!array_key_exists('Discussion', $this->Data)) {
            $this->setData('Discussion', $this->DiscussionModel->getForeignID($foreignId), true);
        }

        if (!is_object($this->Discussion)) {
            throw notFoundException('Discussion');
        }

        $this->index($this->Discussion->DiscussionID, '', $page);
    }

    /**
     * Checks that a string appears to be in the format of a UUID.
     *
     * @param  string $uuid
     *
     * @return bool
     */
    protected function isValidUuid($uuid)
    {
        $pattern = '[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}';

        return (bool) preg_match('/^'.$pattern.'$/', $uuid);
    }
}
