<?php

/**
* Generate product XML feed
*
* @package    RahulMage
* @subpackage ProductFeed
* @author  Rahul Dadhich (http://rahuldadhich.com)
*/


class RahulMage_ProductFeed_Model_Cron{	

	public function getGoogleXMLFeed(){

		$store_title = Mage::getStoreConfig('design/head/default_title');
		$storeURL = Mage::getBaseUrl();

		// get products...

		$products = Mage::getModel('catalog/product')->getCollection()
	        ->setStoreId(Mage::app()->getStore()->getId())
	        ->addAttributeToFilter('status', 1)
	        ->addAttributeToSelect(array('sku', 'name', 'short_description', 'small_image', 'url_path', 'news_from_date', 'news_to_date', 'special_from_date', 'special_price', 'special_to_date', 'price', 'manufacturer'))
	        ->setOrder('entity_id', 'DESC');

        // products filter = get only stocked products
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($products);

        $feed_data = '<?xml version="1.0" ?>'."\n";
		$feed_data .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">'."\n";
		$feed_data .= "\t".'<channel>'."\n";
		$feed_data .= "\t\t".'<title>'.$store_title.'</title>'."\n";
		$feed_data .= "\t\t".'<link>'.$storeURL.'</link>'."\n";
		$feed_data .= "\t\t".'<description>This is a sample feed containing the required and recommended attributes for a variety of different products</description>'."\n";

		if(!empty($products)) {
			foreach($products as $product) {

				// get product categories
			    foreach($product->getCategoryIds() as $catId) {
			        $productType = $categoryTree = array();
			        $path = Mage::getModel('catalog/category')->load($catId)->getPath();
			        if($path != '') {
			            $ids = array_slice(explode('/', $path), 2);
			            if(!empty($ids)) {
			                foreach($ids as $id)
			                    $productType[] = $categoryTree[] = Mage::getModel('catalog/category')->load($id)->getName();
			            }
			        }
			        break;
			    }

			    if($product->getShortDescription())
			        $shortDescription = $product->getShortDescription();
			    else
			        $shortDescription = "Buy ".$product->getName()." at Rs. ".((number_format($product->getFinalPrice(),2) != number_format($product->getPrice(),2))?number_format($product->getFinalPrice(),2):number_format($product->getPrice(),2))." - ".$store_title;
			    
			    array_pop($productType);

			    if($product->getPrice() != 0) {
			        $feed_data .= "\t\t".'<item>'."\n";
			        $feed_data .= "\t\t\t".'<g:id><![CDATA['.$product->getId().'/'.$product->getSku().']]></g:id>'."\n";
			        $feed_data .= "\t\t\t".'<title><![CDATA['.$product->getName().']]></title>'."\n";
			        $feed_data .= "\t\t\t".'<description><![CDATA['.$shortDescription.']]></description>'."\n";
			        $feed_data .= "\t\t\t".'<g:google_product_category><![CDATA['.implode('>', $categoryTree).']]></g:google_product_category>'."\n";
			        $feed_data .= "\t\t\t".'<g:product_type><![CDATA['.implode('>', ((count($productType))?$productType:$categoryTree)).']]></g:product_type> '."\n";
			        $feed_data .= "\t\t\t".'<link><![CDATA['.$product->getProductUrl().']]></link>'."\n";
			        if(file_exists(Mage::getBaseDir('media').DS.'catalog/product'.$product->getSmallImage()))
			            $feed_data .= "\t\t\t".'<g:image_link><![CDATA['.Mage::getBaseUrl('media').'catalog/product'.$product->getSmallImage().']]></g:image_link>'."\n";
			        $feed_data .= "\t\t\t".'<g:condition>'.$new.'</g:condition>'."\n";
			        $feed_data .= "\t\t\t".'<g:availability>'.'in stock'.'</g:availability>'."\n";
			        // $feed_data .= "\t\t\t".'<g:availability>'.(($product->getIsInStock())?'in stock':'out of stock').'</g:availability>'."\n";
			        $feed_data .= "\t\t\t".'<g:price>'.sprintf('%0.2f', $product->getPrice()).' INR</g:price>'."\n";
			        if(sprintf('%0.2f', $product->getFinalPrice()) != sprintf('%0.2f', $product->getPrice()))
			            $feed_data .= "\t\t\t".'<g:sale_price>'.sprintf('%0.2f', $product->getFinalPrice()).' INR</g:sale_price>'."\n";

			        $feed_data .= "\t\t\t".'<g:brand><![CDATA['.$product->getAttributeText('manufacturer').']]></g:brand>'."\n";
			        $feed_data .= "\t\t".'</item>'."\n";
			    }
			}
		}

		$feed_data .= "\t".'</channel>'."\n";
		$feed_data .= '</rss>'."\n";

		// write to file...

		$fileDir = Mage::getBaseDir('media').DS.'GMC';
		$filePath = Mage::getBaseDir('media').DS.'GMC'.DS.'automation.xml';

		if(file_exists($filePath))
		    unlink($filePath);

		if(!file_exists($fileDir)) {
		    mkdir($fileDir);
		    chmod($fileDir, 0777);
		}

		if(is_dir($fileDir)) {
		    $fp = fopen($filePath, 'w');
		    fwrite($fp, $feed_data);
		    fclose($fp);
		}
	}
}