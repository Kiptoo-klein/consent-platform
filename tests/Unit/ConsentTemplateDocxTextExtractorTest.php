<?php

namespace Tests\Unit;

use App\Services\ConsentTemplateDocxTextExtractor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;

class ConsentTemplateDocxTextExtractorTest extends TestCase
{
    public function test_it_extracts_formatted_word_content_and_images(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $run = $section->addTextRun();
        $run->addText(
            'Patient Consent Form',
            ['bold' => true, 'size' => 18]
        );

        $section->addText(
            'I consent to the stated procedure.',
            ['italic' => true]
        );

        $section->addListItem(
            'I had an opportunity to ask questions.'
        );

        $imagePath = tempnam(
            sys_get_temp_dir(),
            'econsent-image-'
        ).'.png';

        file_put_contents(
            $imagePath,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB'
                .'CAQAAAC1HAwCAAAAC0lEQVR42mP8/x8A'
                .'AgMBgN2X6nQAAAAASUVORK5CYII='
            )
        );

        $section->addImage(
            $imagePath,
            ['width' => 40, 'height' => 40]
        );

        $path = tempnam(
            sys_get_temp_dir(),
            'econsent-docx-'
        ).'.docx';

        try {
            IOFactory::createWriter(
                $phpWord,
                'Word2007'
            )->save($path);

            $result = (
                new ConsentTemplateDocxTextExtractor()
            )->extract($path);

            $this->assertStringContainsString(
                'Patient Consent Form',
                $result['text']
            );

            $this->assertStringContainsString(
                'font-weight: bold',
                $result['html']
            );

            $this->assertStringContainsString(
                'data:image/png;base64,',
                $result['html']
            );

            $this->assertNotEmpty(
                $result['warnings']
            );
        } finally {
            @unlink($path);
            @unlink($imagePath);
        }
    }
}
