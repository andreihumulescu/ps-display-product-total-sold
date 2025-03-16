<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT Free License
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/mit
 *
 * @author    Andrei H
 * @copyright Since 2025 Andrei H
 * @license   MIT
 */
$autoloader = dirname(__FILE__) . '/vendor/autoload.php';

if (is_readable($autoloader)) {
    include_once $autoloader;
}

use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DisplayProductTotalSold extends Module
{
    private const HOOKS = [
        'actionProductGridDefinitionModifier',
        'actionProductGridQueryBuilderModifier',
    ];

    public function __construct()
    {
        $this->name = 'displayproducttotalsold';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Andrei H';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Display Product Total Sold', [], 'Modules.Displayproducttotalsold.Admin');
        $this->description = $this->trans('PrestaShop module that displays the number of sold products', [], 'Modules.Displayproducttotalsold.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Displayproducttotalsold.Admin');
    }

    /**
     * {@inheritDoc}
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->registerHook(self::HOOKS);
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function hookActionProductGridDefinitionModifier(array $params)
    {
        if (empty($params['definition'])) {
            return;
        }

        $definition = $params['definition'];

        $definition->getColumns()->addAfter(
            'quantity',
            (new DataColumn('total_sold'))
                ->setName($this->trans('Total Sold', [], 'Modules.Displayproducttotalsold.Admin'))
                ->setOptions([
                    'field' => 'total_sold',
                ])
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hookActionProductGridQueryBuilderModifier(array $params)
    {
        if (empty($params['search_query_builder']) || empty($params['search_criteria'])) {
            return;
        }

        /** @var Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $searchQueryBuilder->addSelect(
            'COALESCE(SUM(od.`product_quantity`), 0) AS `total_sold`'
        );

        $searchQueryBuilder->leftJoin(
            'p',
            '`' . _DB_PREFIX_ . 'order_detail`',
            'od',
            'od.`product_id` = p.`id_product`'
        );

        $searchQueryBuilder->groupBy('p.id_product');
    }
}
