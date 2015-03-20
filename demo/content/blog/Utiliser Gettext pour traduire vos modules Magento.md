<!--
title: "Utiliser Gettext pour traduire vos modules Magento"
date: 2010-11-03
tags: [Gettext, Magento, traduction]
categories: Magento
-->
Nativement, Magento permet la gestion des traductions de l'application via des fichiers CSV (Comma Separated Values). Il s'agit d'une solution simple, robuste et facile à utiliser pour le néophyte.

En pratique, chaque ligne d'un fichier de langue Magento (par exemple Mage_Core.csv) forme une paire : contenu d'origine puis contenu traduit, séparés par une virgule.
Par exemple : "Add Product","Ajouter un produit"

Comme vous le voyez, rien de plus simple pour traduire son module Magento en français : il suffit de recenser toutes les chaînes de caractère utilisées, de les copier dans un fichier et de les traduire !

Oui c'est facile, car ce type de fichier peut être créé avec n'importe éditeur de texte et sa structure est très simple à comprendre. Néanmoins, lorsqu'un module commence à contenir des centaines de chaînes de caractères, la maintenance devient rapidement pénible :

* quels textes ont déjà été traduit ?
* comment déterminer - facilement - les textes restant à traduire
* quels textes sont obsolète ? (la formulation à été changée)
* il n'est pas évident de maintenir un dictionnaire des termes génériques
* comment assurer la bonne édition du fichier s'il est éditer par plusieurs contributeurs
* etc

Mon expérience Drupal m'a habitué à ne pas me casser la tête avec la traduction de mes modules. Pourquoi ? Parce-que Drupal utilise Gettext pour PHP ! Gettext est un mécanisme de traduction libre, s'appuyant sur les locales d'un système d'application, le tout stocké dans des fichiers binaires (.mo). Je vous invite à suivre le lien suivant pour plus d'infos : GNU gettext utilities.

Maintenant la question est : comment utiliser Gettext avec Magento ?

Pour rappel, Magento est basé sur le Zend Framework, qui fourni l'outillage adéquate les application multilingues : Zend_Translate.
En étudiant un peu la documentation, on s’aperçoit (et c'était prévisible) que Zend_Translate supporte plusieurs formats de traduction, dont Gettext.

Dans le cas de Magento, la Core Team à opté pour un l'adaptateur CSV, mais le ZF propose également le support de Gettext.

Voyons maintenant comment l’utiliser, en créant un module simple, qui va overrider la classe de traduction de Magento.

Note : Le code qui suit n'est fonctionnel tel quel, il s'agit de portion de code. Si j'ai le courage, je packagerai un module téléchargeable.

app/code/local/Narno/Gettext/Model/Translate.php

```
/**
 * Narno Gettext Translate model
 *
 * New feature: support Gettext file (binary).
 */
class Narno_Gettext_Model_Translate extends Mage_Core_Model_Translate
{
    /**
     * Config file: translate files types
     */
    const TYPE_DEFAULT = 'csv'; // xml path: translate/modules/[My_Module]/files/default
    const TYPE_CSV     = 'csv'; // xml path: translate/modules/[My_Module]/files/csv
    const TYPE_MO      = 'mo';  // xml path: translate/modules/[My_Module]/files/mo

    /**
     * Loading data from module translation files
     *
     * @param   string $moduleName
     * @param   string $files
     * @return  Mage_Core_Model_Translate
     */
    protected function _loadModuleTranslation($moduleName, $files, $forceReload=false)
    {
        foreach ($files as $type=>$file) {
            $file = $this->_getModuleFilePath($moduleName, $file);
            $this->_addData($this->_getFileData($file, $type), $moduleName, $forceReload);
        }
        return $this;
    }

    /**
     * Retrieve data from file
     *
     * @param   string $file
     * @param   string $type
     * @return  array
     */
    protected function _getFileData($file, $type)
    {
        $data = array();
        if (file_exists($file)) {
            switch ($type) {
                case self::TYPE_MO:
                    $getText = new Zend_Translate_Adapter_Gettext($file, $this->getLocale());
                    $data = $getText->getMessages();
                    break;
                case self::TYPE_CSV:
                case self::TYPE_DEFAULT:
                default:
                    $parser = new Varien_File_Csv();
                    $parser->setDelimiter(self::CSV_SEPARATOR);
                    $data = $parser->getDataPairs($file);
                    break;
            }
        }
        return $data;
    }
}
```

Narno/Gettext/etc/config.xml

```
<config>
    <frontend>
        <translate>
            <modules>
                <Narno_Gettext>
                    <files>
                        <mo>Narno_Gettext.mo</mo>
                    </files>
                </Narno_Gettext>
            </modules>
        </translate>
    </frontend>
</config>
```

Si vous comparez la classe Narno_Gettext_Model_Translate avec la classe originale Mage_Core_Model_Translate, vous constaterez que j'ai introduis la possibilité de déterminer le type du fichier de traduction. De ce fait, le format CSV reste utilisable, via le noeud XML "default" (ou CSV). Pour charger un fichier binaire Gettest, il suffit d'utiliser le type "MO".

En ce qui concerne l’accès aux données binaires stockées dans le fichier .mo, rien de plus simple avec ZF en instanciant l'objet adéquate, en passant en paramètre le fichier de traduction et la locale correspondante. Magento utilisant déjà les locales, aucune bidouille n'est nécessaire :

```
$getText = new Zend_Translate_Adapter_Gettext($file, $this->getLocale());
$data = $getText->getMessages();
```

Voilà, c'est tout ! :-)

Néanmoins, je dois reconnaître que si ma solution est simple, elle n'est pas des plus élégante... en effet, je me contente de modifier la méthode de chargement du fichier de langue, alors qu'il serait plus pertinent de réécrire les adaptateurs de Magento afin de prendre en compte Gettext en amont. Mais je laisse ce travail à la Core Team Magento ! :-P

A partir de là, vous allez pouvoir commencer à vous amuser grâce à des logiciels de gestion catalogues Gettext, tels que Poedit (disponible pour Windows, Mac et Linux).

Vous verrez que c'est très agréable à utiliser et que c'est nettement plus adapté à un usage professionnel : vous n'aurez plus à recherche manuellement les chaînes à traduire au milieu de vos lignes de codes, le logiciel le fera pour vous en scannant les répertoires de votre module ! Ce sera certainement le sujet du prochain billet ;-)
