<?php

namespace Tests\Unit;

use App\Services\ConsentTemplateDocxTextExtractor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;

class ConsentTemplateDocxTextExtractorTest extends TestCase
{
    public function test_it_extracts_word_content(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText('Patient Consent Form');
        $section->addText(
            'I consent to the stated procedure.'
        );
        $section->addListItem(
            'I had an opportunity to ask questions.'
        );

        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('Signer name');
        $row->addCell()->addText('Signature date');

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
                'I consent to the stated procedure.',
                $result['text']
            );

            $this->assertStringContainsString(
                'I had an opportunity to ask questions.',
                $result['text']
            );

            $this->assertStringContainsString(
                'Signer name | Signature date',
                $result['text']
            );

            $this->assertNotEmpty($result['warnings']);
        } finally {
            @unlink($path);
        }
    }
}
