<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\BaseList;
use SebLucas\Cops\Calibre\Tag;
use SebLucas\Cops\Input\Config;

class PageAllTags extends Page
{
    protected $className = Tag::class;

    public function InitializeContent()
    {
        $this->getEntries();
        $this->idPage = Tag::PAGE_ID;
        $this->title = localize("tags.title");
    }

    public function getEntries()
    {
        $baselist = new BaseList($this->className, $this->request);
        $this->sorted = $this->request->getSorted("sort");
        if (!empty(Config::get('calibre_categories_using_hierarchy')) && in_array($this->className::CATEGORY, Config::get('calibre_categories_using_hierarchy'))) {
            // use tag_browser_tags view here, to get the full hierarchy?
            $this->entryArray = $baselist->browseAllEntries($this->n);
            $this->hierarchy = true;
        } else {
            $this->entryArray = $baselist->getRequestEntries($this->n);
        }
        $this->totalNumber = $baselist->countRequestEntries();
        $this->sorted = $baselist->orderBy;
        if ((!$this->isPaginated() || $this->n == $this->getMaxPage()) && in_array("tag", Config::get('show_not_set_filter'))) {
            array_push($this->entryArray, $baselist->getWithoutEntry());
        }
    }
}
