<?php

namespace Impulse\Pulsifier\Traits;

use Impulse\Pulsifier\Helpers\Seek;

trait HasPulseAttribute
{
    /**
     * @var array of relations to be eager load
     **/
    protected $eager_loaded_relations = [];

    /**
     * @uses assign values in constructor of the model.
     * @var array of searchable properties to be included during search operation.
     **/
    protected $searchable = [];

    /**
     * @uses assign values in constructor of the model.
     * @var array of savable relation to be included during save.
     **/
    protected $savable_relations = [];

    /**
     * @return array $eager_loaded_relations
     */
    public function getEagerLoadedRelations()
    {
        return $this->eager_loaded_relations;
    }

    /**
     * @param bool $line_break add line break to separator (default:false)
     * @return string comma separated $eager_loaded_relations
     */
    public function getEagerLoadedRelationsString($line_break = false)
    {
        $str = implode($line_break ? "','" . PHP_EOL : "','", $this->eager_loaded_relations);
        if(empty($str))
            return "[];";
        return !$line_break ? "['" . $str . "'];" : "'" . ltrim($str, PHP_EOL) . "'";
    }

    public function setSearchMethod()
    {
        return str_replace("{{generatedQuery}}", trim($this->createSearchMethodRecursive($this->searchable), PHP_EOL) . ";", PS_SEARCHABLE);
    }

    private function createSearchMethodRecursive($searchable)
    {
        $searchString = "";
        if (!empty($searchable)) {
            foreach ($searchable as $value) {
                if (!is_array($value))
                    continue;
                $str = str_replace(
                    ['{{field}}', '{{search}}'],
                    [$value['field'], '$this->searchTerm'],
                    $value['template']
                );
                if (isset($value['seekers'])) {
                    $subQuery = $this->createSearchMethodRecursive($value['seekers']);
                    $str = str_replace('{{subQuery}}', trim($subQuery, PHP_EOL), $str);
                }
                $searchString .= $str . PHP_EOL;
            }
        }
        return $searchString;
    }

    /**
     * @return array $savable_relations
     * @throws \ReflectionException
     */
    public function getSavableRelations()
    {
        $grouped_relations = [];
        $className = get_class($this);
        $reflection = new \ReflectionClass($className);

        foreach ($this->savable_relations as $savable_relation) {
            if (method_exists($this, $savable_relation)) {
                $block = Seek::getMethodBlock($reflection->getShortName() . ".php", $savable_relation);
                $grouped_relations[] = [
                    'type' => Seek::getBlockSavableRelationType($block),
                    'method_name' => $savable_relation,
                    'class_name' => Seek::getBlockSavableRelationClassName($block),
                    'block_64' => base64_encode($block)
                ];
            }
        }
        return $grouped_relations;
    }

    public function getAllAttributes(){
        $fillables = $this->getFillable();
        $hidden = $this->getHidden();
        return array_unique (array_merge ($fillables, $hidden));
    }
}
