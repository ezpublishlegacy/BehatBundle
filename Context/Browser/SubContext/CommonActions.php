<?php
/**
 * File containing the Common Actions for Browser contexts
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\Context\Browser\SubContext;

use EzSystems\BehatBundle\Helper\EzAssertion;
use EzSystems\BehatBundle\Helper\Gherkin as GherkinHelper;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use PHPUnit_Framework_Assert as Assertion;

/**
 * Class with the simple actions you can do in a browser
 */
trait CommonActions
{
    /**
     * @When I fill in :field with :value
     */
    public function fillFieldWithValue( $field, $value )
    {
        $this->getSession()->getPage()->fillField( $field, $value );
    }

    /**
     * @Given I am on :path
     * @When  I go to :path
     */
    public function visit( $path )
    {
        $this->getSession()->visit( $this->locatePath( $path ) );
    }

    /**
     * @Given I am on/at the homepage
     * @Given I am on/at (the) :page page
     * @When I go to the homepage
     * @When  I go to (the) :page page
     */
    public function iAmOnPage( $page = 'home' )
    {
        $this->visit( $this->getPathByPageIdentifier( $page ) );
        try
        {
            $statusCode = $this->getSession()->getStatusCode();
            $valid = false;
            if ( $statusCode >= 200 && $statusCode < 400 )
            {
                $valid = true;
            }
            Assertion::assertTrue( $valid, "Invalid response status code '{$statusCode}'" );
        }
        // several mink drivers do not support getStatusCode()
        catch ( UnsupportedDriverActionException $e )
        {
            // do nothing
        }
    }

    /**
     * @Then I should be at/on (the) homepage
     * @Then I should be at/on (the) :page page
     */
    public function iShouldBeOnPage( $pageIdentifier = 'home' )
    {
        $currentUrl = $this->getUrlWithoutQueryString( $this->getSession()->getCurrentUrl() );

        $expectedUrl = $this->locatePath( $this->getPathByPageIdentifier( $pageIdentifier ) );

        Assertion::assertEquals(
            $expectedUrl,
            $currentUrl,
            "Unexpected URL of the current site. Expected: '$expectedUrl'. Actual: '$currentUrl'."
        );
    }

    /**
     * @Given I clicked on/at (the) :button button
     * @When I click on/at (the) :button button
     */
    public function iClickAtButton( $button )
    {
        $this->onPageSectionIClickAtButton( $button );
    }

    /**
     * @Given on :pageSection I clicked at/on :button button
     * @When  on :pageSection I click at/on :button button
     */
    public function onPageSectionIClickAtButton( $button, $pageSection = null )
    {
        $base = $this->makeXpathForBlock( $pageSection );
        $el = $this->getXpath()->findButtons( $button, $base );
        EzAssertion::assertElementFound( $button, $el, $pageSection, 'button' );
        $el[0]->click();
    }

    /**
     * @Given I clicked on/at (the) :link link
     * @When  I click on/at (the) :link link
     */
    public function iClickAtLink( $link )
    {
        $this->onPageSectionIClickAtLink( $link );
    }

    /**
     * @Given on :pageSection I clicked on/at link link
     * @When  on :pageSection I click on/at :link link
     */
    public function onPageSectionIClickAtLink( $link, $pageSection = null )
    {
        $base = $this->makeXpathForBlock( $pageSection );
        $el = $this->getXpath()->findLinks( $link, $base );
        EzAssertion::assertElementFound( $link, $el, $pageSection, 'link' );
        $el[0]->click();
    }

    /**
     * @Given I checked :label checkbox
     * @When  I check :label checkbox
     */
    public function checkOption( $option )
    {
        $fieldElements = $this->getXpath()->findFields( $option );
        EzAssertion::assertElementFound( $option, $fieldElements, null, 'checkbox' );

        // this is needed for the cases where are checkboxes and radio's
        // side by side, for main option the radio and the extra being the
        // checkboxes values
        if ( strtolower( $fieldElements[0]->getAttribute( 'type' ) ) !== 'checkbox' )
        {
            $value = $fieldElements[0]->getAttribute( 'value' );
            $fieldElements = $this->getXpath()->findXpath( "//input[@type='checkbox' and @value='$value']" );
            EzAssertion::assertElementFound( $value, $fieldElements, null, 'checkbox' );
        }

        $fieldElements[0]->check();
    }

    /**
     * @When I select :option
     *
     * IMPORTANT:
     *  This will thrown an error if it find's more than 1 select/dropdown on page
     */
    public function iSelect( $option )
    {
        $elements = $this->getXpath()->findXpath( "//select" );
        Assertion::assertNotEmpty( $elements, "Unable to find a select field" );
        $elements[0]->selectOption( $option );
    }

    /**
     * @Given I selected :label radio button
     * @When  I select :label radio button
     */
    public function iSelectRadioButton( $label )
    {
        $el = $this->getSession()->getPage()->findField( $label );
        Assertion::assertNotNull( $el, "Couldn't find a radio input with '$label'" );
        $el->check();
    }

     /**
     * @Given I filled form with:
     * @When  I fill form with:
     */
    public function iFillFormWith( TableNode $table )
    {
        foreach ( GherkinHelper::convertTableToArrayOfData( $table ) as $field => $value )
        {
            $elements = $this->getXpath()->findFields( $field );
            Assertion::assertNotEmpty( $elements, "Unable to find '{$field}' field" );
            $elements[0]->setValue( $value );
        }
    }

    /**
     * @Then I (should) see (the) (following) links:
     *      | link          |
     *      | some link     |
     *      | another link  |
     *      ...
     *      | the link      |
     */
    public function iSeeLinks( TableNode $table )
    {
        $this->onPageSectionISeeLinks( $table );
    }

    /**
     * @Then on :pageSection I (should) see (the) (following) links:
     */
    public function onPageSectionISeeLinks( TableNode $table, $pageSection = null )
    {
        $rows = $table->getRows();
        array_shift( $rows );

        foreach ( $rows as $row )
        {
            $link = $row[0];
            $el = $this->getXpath()->findLinks( $link, $this->makeXpathForBlock( $pageSection ) );

            Assertion::assertNotEmpty( $el, "Unexpected link found" );
        }
    }

    /**
     * @Then I shouldn't see (the) (following) links:
     * @Then I don't see (the) (following) links:
     */
    public function iDonTSeeLinks( TableNode $table )
    {
        $this->onPageSectionIDonTSeeLinks( 'main', $table );
    }

    /**
     * @Then on :pageSection I shouldn't see (the) (following) links:
     * @Then on :pageSection I don't see (the) (following) links:
     */
    public function onPageSectionIDonTSeeLinks( TableNode $table, $pageSection = null )
    {
        $rows = $table->getRows();
        array_shift( $rows );

        foreach ( $rows as $row )
        {
            $link = $row[0];
            $el = $this->getXpath()->findLinks( $link, $this->makeXpathForBlock( $pageSection ) );

            Assertion::assertEmpty( $el, "Unexpected link found" );
        }
    }

    /**
     * @Then I (should) see (the) links in the following order:
     * @Then I (should) see (the) links in this order:
     */
    public function iSeeLinksInFollowingOrder( TableNode $table )
    {
        // get all links
        $available = $this->getXpath()->findXpath( "//a[@href]" );

        $rows = $table->getRows();
        array_shift( $rows );

        // remove links from embeded arrays
        $links = array();
        foreach ( $rows as $row )
        {
            $links[] = $row[0];
        }

        // and finaly verify their existence
        $this->checkLinksExistence( $links, $available );

    }

    /**
     * @Then I (should) see (the) (following) links in:
     *      | link  | tag   |
     *      | link1 | title |
     *      | link2 | list  |
     *      | link3 | text  |
     *
     * Example: this is used to see in tag cloud which tags have more results
     *
     */
    public function iSeeFollowingLinksIn( TableNode $table )
    {
        $session = $this->getSession();
        $rows = $table->getRows();
        array_shift( $rows );
        foreach ( $rows as $row )
        {
            Assertion::assertEquals( 2, count( $row ), "The table should be have array with link and tag" );

            // prepare XPath
            list( $link, $type ) = $row;
            $tags = $this->getTagsFor( $type );
            $xpaths = explode( '|', $this->getXpath()->makeElementXpath( 'link', $link ) );
            $xpath = implode(
                '|',
                array_map(
                    function( $tag ) use( $xpaths )
                    {
                        return "//$tag/" . implode( "| //$tag/", $xpaths );
                    },
                    $tags
                )
            );

            // search and do assertions
            $el = $this->getXpath()->findXpath( $xpath );
            EzAssertion::assertSingleElement( $link, $el, $type, 'link' );
        }
    }

    /**
     * @Then I (should) see :title title/topic
     */
    public function iSeeTitle( $title )
    {
        $literal = $this->getXpath()->literal( $title );
        $tags = $this->getTagsFor( "title" );
        $innerXpath = "[text() = {$literal} or .//*[text() = {$literal}]]";
        $xpathOptions = array_map(
            function( $tag ) use( $innerXpath )
            {
                return "//$tag$innerXpath";
            },
            $tags
        );

        $xpath = implode( '|', $xpathOptions );

        $el = $this->getXpath()->findXpath( $xpath );

        // assert that message was found
        EzAssertion::assertSingleElement( $title, $el, null, 'title' );
    }

    /**
     * @Then I (should) see table with:
     *      | Column 1 | Column 2 | Column 4 |
     *      | Value A  | Value B  | Value D  |
     *      ...
     *      | Value I  | Value J  | Value L  |
     *
     * The table header needs to have the number of the column which column
     * values belong, all the other text is optional, normaly using 'Column' for
     * easier understanding
     */
    public function iSeeTableWith( TableNode $table )
    {
        $rows = $table->getRows();
        $headers = array_shift( $rows );

        $max = count( $headers );
        $mainHeader = array_shift( $headers );
        foreach ( $rows as $row )
        {
            $mainColumn = array_shift( $row );
            $foundRows = $this->getTableRow( $mainColumn, $mainHeader );

            $found = false;
            $maxFound = count( $foundRows );
            for ( $i = 0; $i < $maxFound && !$found; $i++ )
            {
                if ( $this->existTableRow( $foundRows[$i], $row, $headers ) )
                {
                    $found = true;
                }
            }

            $message = "Couldn't find row with elements: '" . implode( ",", array_merge( array( $mainColumn ), $row ) ) . "'";
            Assertion::assertTrue( $found, $message );
        }
    }

    /**
     * @Then I (should) see :text text emphasized
     */
    public function iSeeTextEmphasized( $text )
    {
        $this->onPageSectionISeeTextEmphasized( $text );
    }

    /**
     * @Then on :pageSection I (should) see the :text text emphasized
     */
    public function onPageSectionISeeTextEmphasized( $text, $pageSection = null )
    {
        // first find the text
        $base = $this->makeXpathForBlock( $pageSection );
        $el = $this->getXpath()->findXpath( "$base//*[contains( text(), {$this->getXpath()->literal( $text )} )]" );

        EzAssertion::assertSingleElement( $text, $el, $pageSection, 'emphasized text' );

        // finally verify if it has custom characteristics
        Assertion::assertTrue(
            $this->isElementEmphasized( $el[0] ),
            "The text '$text' isn't emphasized"
        );
    }

    /**
     * @Then I (should) see :warning warning/error
     */
    public function iSeeWarning( $warning )
    {
        $el = $this->getXpath()->findXpath(
            "//*[contains( @class, 'warning' ) or contains( @class, 'error' )]"
            . "//*[text() = {$this->getXpath()->literal( $warning )}]"
        );

        Assertion::assertNotNull( $el, "Couldn't find error/warning message '{$warning}'" );
    }

    /**
     * @Then I (should) see the exact :text: message/text
     */
    public function iSeeText( $text )
    {
        $this->onPageSectionISeeText( $text );
    }

    /**
     * @Then on :pageSection I (should) see the exact :text message/text
     */
    public function onPageSectionISeeText( $text, $pageSection = null )
    {
        $base = $this->makeXpathForBlock( $pageSection );

        $literal = $this->getXpath()->literal( $text );
        $el = $this->getXpath()->findXpath( "$base//*[contains( text(), $literal )]" );

        Assertion::assertNotNull( $el, "Couldn't find '$text' text" );
        Assertion::assertEquals( trim( $el->getText() ), $text, "Couldn't find '$text' text" );
    }

    /**
     * @Then I (should) see :message message/text
     */
    public function iSeeMessage( $text )
    {
        $this->assertSession()->pageTextContains( $text );
    }

    /**
     * @Then I don't see :text message/text
     */
    public function iDonTSeeMessage( $text )
    {
        $this->assertSession()->pageTextNotContains( $text );
    }
}