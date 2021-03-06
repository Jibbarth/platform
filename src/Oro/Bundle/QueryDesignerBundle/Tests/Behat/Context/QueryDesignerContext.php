<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * The context for testing Query Designer related features.
 */
class QueryDesignerContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @When I add the following columns:
     *
     * @param TableNode $table
     */
    public function iAddTheFollowingColumns(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($column, $functionName) = array_pad($row, 2, null);
            $this->addColumns(explode('->', $column), $functionName);
        }
    }

    /**
     * @When I add the following grouping columns:
     *
     * @param TableNode $table
     */
    public function iAddTheFollowingGroupingColumns(TableNode $table)
    {
        foreach ($table->getRows() as list($column)) {
            $this->addGroupingColumns(explode('->', $column));
        }
    }

    /**
     * Selects field for "Grouping by date -> Date Field"
     * Example: When I select "Created At" from date grouping field
     *
     * @When /^(?:|I )select "(?P<field>(?:[^"]|\\")*)" from date grouping field$/
     *
     * @param string $field
     */
    public function selectDateGroupingField($field)
    {
        $field = $this->fixStepArgument($field);
        $dateField = $this->createElement('Date Field')->getParent();
        $dateField->clickLink('Choose a field');
        $this->selectField([$field]);
    }

    /**
     * @param string[] $columns
     * @param string   $functionName
     */
    private function addColumns($columns, $functionName)
    {
        $this->clickLinkInColumnDesigner('Choose a field');
        $this->selectField($columns);
        if ($functionName) {
            $this->setFunctionValue($functionName);
        }
        $this->clickLinkInColumnDesigner('Add');
    }

    /**
     * @param string[] $columns
     */
    private function addGroupingColumns($columns)
    {
        $this->clickLinkInGroupingDesigner('Choose a field');
        $this->selectField($columns);
        $this->clickLinkInGroupingDesigner('Add');
    }

    /**
     * @param string $link
     */
    private function clickLinkInColumnDesigner($link)
    {
        $columnDesigner = $this->createElement('Columns');
        $columnDesigner->clickLink($link);
    }

    /**
     * @param string $link
     */
    private function clickLinkInGroupingDesigner($link)
    {
        $groupingDesigner = $this->createElement('Grouping');
        $groupingDesigner->clickLink($link);
    }

    /**
     * @param string[] $path
     */
    private function selectField(array $path)
    {
        foreach ($path as $key => $column) {
            $typeTitle = $key === count($path) - 1 ? 'Fields' : 'Related entities';
            $this->getPage()
                ->find('xpath', "//div[@id='select2-drop']/div/input")
                ->setValue($column);
            $this->getPage()
                ->find(
                    'xpath',
                    sprintf(
                        "//div[@id='select2-drop']//div[contains(.,'%s')]/..//div[contains(.,'%s')]",
                        $typeTitle,
                        $column
                    )
                )
                ->click();
        }
    }

    /**
     * @param string $value
     */
    private function setFunctionValue($value)
    {
        $columnFunction = $this->createElement('Column Function');
        $columnFunction->selectOption($value);
    }
}
