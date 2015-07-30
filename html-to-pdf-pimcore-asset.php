<?php
/**
 * Grabbing the PDF-object via stream didn't work, so I wrote this little function
 * Uses https://github.com/mikehaertl/phpwkhtmltopdf but this will work with any other library too
 */
use Pimcore\Model\Asset;
 
public function htmlToPdfPimcoreAsset() {
    try {
        require_once('YOUR/PATH/WkHtmlToPdf.php');
        $pdf = new WkHtmlToPdf();
        $pdf->addPage('http://THE_PAGE_YOU_WANT_TO_SAVE_AS_PDF');

        $filePath = 'YOUR/PIMCORE/ASSETS/FILEPATH';
        $fileName = 'YOUR_FILE_NAME';
        // If the PDF-file to generate is allready existing inside pimcore's Assets, add '_1'
        if (Asset::getByPath($filePath . $fileName . '.pdf')) {
            $fileName .= '_1';
        }
        $fileName .=  '.pdf';

        // Generate a new pimcore-Asset for the PDF-document
        $asset = new Asset();
        $asset->setCreationDate(time());
        $asset->setParentId(Asset_Folder::getByPath($filePath)->getId());
        $asset->setFilename($fileName);

        // Save the WkHtmlToPdf-object to a tmp-directory so the pimcore-Asset can be generated out of it
        $tmpFile = '/var/www/pdf-tmp/' . $fileName;
        $pdf->saveAs($tmpFile);

        // Fill the pimcore-Asset with the tmp-PDF
        $asset->setData(file_get_contents($tmpFile));
        $asset->save();

        return true;
    }  catch(Exception $e) {
        return $e->getMessage();
    }
}
?>
