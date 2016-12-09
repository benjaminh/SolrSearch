<?php

/**
 * @package     omeka
 * @subpackage  solr-search
 * @copyright   2012 Rector and Board of Visitors, University of Virginia
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 */


class SolrSearch_Helpers_Facet
{


    /**
     * Convert $_GET into an array with exploded facets.
     *
     * @return array The parsed parameters.
     */
    public static function parseFacets()
    {

        $facets = array();

        if (array_key_exists('facet', $_GET)) {

            // Extract the field/value facet pairs.
            preg_match_all('/(?P<field>[\w]+):"(?P<value>[^"]+)"/',
                $_GET['facet'], $matches
            );

            // Collapse into an array of pairs.
            foreach ($matches['field'] as $i => $field) {
                $facets[] = array($field, $matches['value'][$i]);
            }

        }

        return $facets;

    }


    /**
     * Rebuild the URL with a new array of facets.
     *
     * @param array $facets The parsed facets.
     * @return string The new URL.
     */
    public static function makeUrl($facets)
    {

        // Collapse the facets to `:` delimited pairs.
        $fParam = array();
        foreach ($facets as $facet) {
            $fParam[] = "{$facet[0]}:\"{$facet[1]}\"";
        }

        // Implode on ` AND `.
        $fParam = urlencode(implode(' AND ', $fParam));

        // Get the `q` parameter, reverting to ''.
        $qParam = array_key_exists('q', $_GET) ? $_GET['q'] : '';

        // Get the base results URL.
        $results = url('solr-search');

        // String together the final route.
        return htmlspecialchars("$results?q=$qParam&facet=$fParam");

    }


    /**
     * Add a facet to the current URL.
     *
     * @param string $field The facet field.
     * @param string $value The facet value.
     * @return string The new URL.
     */
    public static function addFacet($field, $value)
    {

        // Get the current facets.
        $facets = self::parseFacets();

        // Add the facet, if it's not already present.
        if (!in_array(array($field, $value), $facets)) {
            $facets[] = array($field, $value);
        }

        // Rebuild the route.
        return self::makeUrl($facets);

    }


    /**
     * Remove a facet to the current URL.
     *
     * @param string $field The facet field.
     * @param string $value The facet value.
     * @return string The new URL.
     */
    public static function removeFacet($field, $value)
    {

        // Get the current facets.
        $facets = self::parseFacets();

        // Reject the field/value pair.
        $reduced = array();
        foreach ($facets as $facet) {
            if ($facet !== array($field, $value)) $reduced[] = $facet;
        }

        // Rebuild the route.
        return self::makeUrl($reduced);

    }


    /**
     * Get the human-readable label for a facet key.
     *
     * @param string $key The facet key.
     * @return string The label.
     */
    public static function keyToLabel($key)
    {
        $fields = get_db()->getTable('SolrSearchField');
        return $fields->findBySlug(rtrim($key, '_s'))->label;
    }

    /**
     * Recursively build a nested HTML unordered list from the provided
     * collection tree.
     *
     * @see CollectionTreeTable::getCollectionTree()
     * @see CollectionTreeTable::getAncestorTree()
     * @see CollectionTreeTable::getDescendantTree()
     * @param array $collectionTree
     * @param bool $linkToCollectionShow
     * @return string
     */
    public static function collectionTreeListFacet($collectionTree)
    {
        if (!$collectionTree) {
            return;
        }
        $collectionTable = get_db()->getTable('Collection');
        $html = '<ul>';
        foreach ($collectionTree as $collection) {
            $html .= '<li class="facet-value">';
            $url   = self::addFacet("collection", $collection['name']);
            $branch = $collection['children'];
            if (!$branch) {
              $html .= '<a href="' . $url . '" class="facet-value">' . $collection['name'] . '</a>';
            }
            else {
              $html .= '<p class="facet-value">' . $collection['name'] . '</p>';
            }
            $html .= self::collectionTreeListFacet($collection['children']);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Use CollectionTree data to create collection facte hierarchy
     *
     *
     */
    public static function collectionTreeFullListFacet()
    {
      $rootCollections = get_db()->getTable('CollectionTree')->getRootCollections();
      // Return NULL if there are no root collections.
      if (!$rootCollections) {
          return null;
      }
      $collectionTable = get_db()->getTable('Collection');
      $html = '<ul>';
      foreach ($rootCollections as $rootCollection) {
          $html .= '<li class="facet-value">';
          $name  = $rootCollection['name'];
          $url   = self::addFacet("collection", $name);

          $collectionTree = get_db()->getTable('CollectionTree')->getDescendantTree($rootCollection['id']);

          if (!$collectionTree) {
            $html .= '<a href="' . $url . '" class="facet-value">' . $name . '</a>';
          }
          else {
            $html .= '<p class="facet-value">' . $name . '</p>';
          }
          $html .= self::collectionTreeListFacet($collectionTree);
          $html .= '</li>';
      }
      $html .= '</ul>';
      return $html;
    }


}
