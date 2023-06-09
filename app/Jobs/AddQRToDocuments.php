<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddQRToDocuments implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $documentGroup;

    public $backoff = 2;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(DocumentGroup $documentGroup)
    {
        $this->documentGroup = $documentGroup;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $documents = $this->documentGroup->documents()->get();

        foreach ($documents as $document) {
            $filename = storage_path()."/app/{$this->documentGroup->id}/{$document->id}.pdf";
            $output_filename = storage_path()."/app/{$this->documentGroup->id}/qr/{$document->id}.pdf";
            logger("Adding QR to {$filename}");
            $output = null;
            $return_value = null;
            $document_link = "https://srv-dide-v.thess.sch.gr/evaluateQR/evaluate/{$document->id}";

            // Δημιούργησε τον φάκελο qr μέσα στον φάκελο της ομάδας γιατί τον χρειαζόμαστε
            if (!file_exists(storage_path(). "/app/{$this->documentGroup->id}/qr")) {
                logger("Create qr folder");
                mkdir(storage_path(). "/app/{$this->documentGroup->id}/qr");
            }

            exec("/usr/bin/qpdfImageEmbed -i {$filename} --qr '{$document_link}' --link --side 2 -o {$output_filename}", $output, $return_value);

            if ($return_value != null && $return_value != 0) {
                $message = "Αποτυχία αποθήκευσης αρχείου '{$this->documentGroup->id}/qr/{$document->id}.pdf'";
                logger($output);
                logger($return_value);
                $this->documentGroup->job_status = DocumentGroup::JobFailed;
                $this->documentGroup->job_status_text = $message;
                $this->documentGroup->save();

                logger($message);
                $this->fail($message);
            }

            $document->state = Document::WithQR;
            $document->save();
            usleep(250000);
        }

        $this->documentGroup->job_status_text = 'Ολοκληρώθηκε η δημιουργία QR';
        $this->documentGroup->save();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->documentGroup->id;
    }
}
