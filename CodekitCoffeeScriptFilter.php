<?php

namespace Bangpound\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CoffeeScriptFilter;
use Assetic\Filter\FilterInterface;

class CodekitCoffeeScriptFilter implements FilterInterface
{
    private $includes;
    private $suffix;
    private $prefix;

    public function __construct(CoffeeScriptFilter $coffee, $prefix = '', $suffix = '')
    {
        $this->coffee = $coffee;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    /**
     * Filters an asset after it has been loaded.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterLoad(AssetInterface $asset)
    {
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();
        $pattern = '/# @codekit-([a-z]+) "(.+?)"/';
        $matches = array();
        preg_match_all($pattern, $asset->getContent(), $matches);

        foreach (array_keys($matches[0]) as $key) {
            $importPath = $matches[2][$key];
            $importRoot = $sourceRoot;
            $importSource = $importRoot.'/'.$importPath;
            $filters = array();
            if (strrpos($importPath, '.coffee', -7)) {
                $filters[] = $this->coffee;
            }
            $include = new FileAsset($importSource, $filters, $importRoot, $importPath);

            $this->includes[$sourceRoot .'/'. $sourcePath][$matches[1][$key]][] = $include;
        }
    }

    /**
     * Filters an asset just before it's dumped.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterDump(AssetInterface $asset)
    {
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();
        if ($this->includes[$sourceRoot .'/'. $sourcePath]) {
            $content = '(function ('. $this->prefix .') {' . PHP_EOL;
            if (isset($this->includes[$sourceRoot .'/'. $sourcePath]['prepend'])) {
                /** @var AssetInterface $include_asset */
                foreach ($this->includes[$sourceRoot .'/'. $sourcePath]['prepend'] as $include_asset) {
                    $filename = basename($include_asset->getSourcePath());
                    $prefix = <<<EOT
  /*
  --------------------------------------------
       Begin $filename
  --------------------------------------------
   */
EOT;

                    $content .= $prefix . PHP_EOL . $include_asset->dump() . PHP_EOL;
                }
            }
            $content .= $asset->getContent();
            if (isset($this->includes[$sourceRoot .'/'. $sourcePath]['append'])) {
                foreach ($this->includes[$sourceRoot .'/'. $sourcePath]['append'] as $include_asset) {
                    $filename = basename($include_asset->getSourcePath());
                    $prefix = <<<EOT
  /*
  --------------------------------------------
       Begin $filename
  --------------------------------------------
   */
EOT;
                    $content .= $prefix . PHP_EOL . $include_asset->dump() . PHP_EOL;
                }
            }
            $content .= PHP_EOL . '})(' . $this->suffix .');';
            $asset->setContent($content);
        }
    }
}
