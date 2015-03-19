<!--
title: "Magento : Ajouter une colonne dans une grille"
date: 2012-09-11
tags: Magento
categories: Magento
-->
Il existe un certain nombre de méthodes pour ajouter une ou plusieurs colonnes dans une grille du back-office Magento, mais il y en a une qui est moins intrusive que les autres : C'est celle-ci que je souhaites partager.

En effet, je vois régulièrement passer des billets, sur des blogs spécialisés en développement Magento, qui proposent des solutions souvent très "bourrines", tel qu'un bon gros overlap des familles : On copie le code d'origine et on ajoute sa ou ses colonnes !
C'est certainement le meilleur moyen de provoquer un conflit avec des extensions agissant sur les grilles ou de brider les mises à jour de Magento. Bref, à éviter !

La solution que je propose n'est pas révolutionnaire : Elle se contente d'exploiter le pattern "[event/observer](http://www.magentocommerce.com/wiki/5_-_modules_and_development/0_-_module_development_in_magento/customizing_magento_using_event-observer_method)" de Magento.

Le principe et la mise en oeuvre sont très simple car Magento tire des évènements avant la construction des blocks et avant le chargement des collections : Exploitons les, tout simplement !

Pour illustrer mes propos, je vais prendre l'exemple de la grille des produits, dans laquelle je veux ajouter une colonne, après celle du SKU, affichant le contenu d'un attribut créer préalablement.

## 1ère étape : Associer un observer au déclenchement d'un évènement

{namespace}_{module}/etc/config.xml :
```xml
<adminhtml> 
    <events> 
        <core_block_abstract_to_html_before> 
            <observers> 
                <{nom_de_mon_observer}> 
                    <type>singleton</type> 
                    <class>{namespace}_{module}/observer</class> 
                    <method>beforeBlockToHtml</method> 
                </{nom_de_mon_observer}> 
            </observers> 
        </core_block_abstract_to_html_before> 
        <eav_collection_abstract_load_before> 
            <observers> 
                <{nom_de_mon_observer}> 
                    <class>{namespace}_{module}/observer</class> 
                    <method>beforeCollectionLoad</method> 
                </{nom_de_mon_observer}> 
            </observers>
        </eav_collection_abstract_load_before> 
    </events> 
</adminhtml>
```

Je pense que le nom des événements est suffisamment explicite, mais dans le doute :

1. "core_block_abstract_to_html_before" est appelé avant qu'un block ne soit chargé dans le layout
2. "eav_collection_abstract_load_before" est appelé avant qu'une collection ne soit chargée et utilisée

Ainsi, je vais ajouter une colonne au moment de la construction des blocks de la grille et je vais enrichir la collection de manière à charger les données correspondant à cette nouvelle colonne.

## 2ème étape : Créer les méthodes de l’observer

{namespace}_{module}/Model/Observer.php :

```php
<?php

class {Namespace}_{Module}_Model_Observer 
{ 
    public function beforeBlockToHtml(Varien_Event_Observer $observer) 
    { 
        $grid = $observer->getBlock(); 

    /** 
     * Mage_Adminhtml_Block_Catalog_Product_Grid 
     */ 
    if ($grid instanceof Mage_Adminhtml_Block_Catalog_Product_Grid) { 
        $grid->addColumnAfter( 
            '{code_de_la_colonne}', 
            array( 
                'header' => Mage::helper('{Module}_catalog')->__('{{nom_de_la_colonne}}'), 
                'index'  => '{code_de_la_colonne}' 
            ), 
            'sku' 
        ); 
    } 
}

public function beforeCollectionLoad(Varien_Event_Observer $observer) 
{ 
    $collection = $observer->getCollection(); 
    if (!isset($collection)) { 
        return; 
    } 

        /** 
         * Mage_Catalog_Model_Resource_Product_Collection 
         */ 
        if ($collection instanceof Mage_Catalog_Model_Resource_Product_Collection) { 
            /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */ 
            $collection->addAttributeToSelect('{code_de_l_attribut}'); 
        } 
    } 
}
```

Explication :

Dans la méthode “beforeBlockToHtml”, qui est appelé quelque soit le type de block, je ne dois intervenir que dans le cas où le block est une instance de la grille des produits.

De là, je n’ai plus qu’à utiliser la méthode “addColumnAfter” sur mon block.

Ensuite, il me faut ajouter mon attribut dans la collection, de manière a afficher la valeur de celui-ci pour chacune des lignes retournées.

Même logique que pour le block : Je vérifie que la collection courante correspond à celle des produits et j’utilise la méthode “addAttributeToSelect”.

Et voilà ! :-)