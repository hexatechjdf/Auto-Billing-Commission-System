<?php
namespace App\Jobs;

use App\Models\Log as LogModel;
use App\Repositories\GhlRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleGhlContactNoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $locationId;
    protected $contactId;
    protected $whiteboard;
    protected $log;
    protected $isNewContact;

    /**
     * Create a new job instance.
     *
     * @param string $locationId
     * @param string $contactId
     * @param string|null $whiteboard
     * @param LogModel $log
     * @param bool $isNewContact
     */
    public function __construct(string $locationId, string $contactId, ?string $whiteboard, LogModel $log, bool $isNewContact = false)
    {
        $this->locationId   = $locationId;
        $this->contactId    = $contactId;
        $this->whiteboard   = $whiteboard;
        $this->log          = $log;
        $this->isNewContact = $isNewContact;
    }

    /**
     * Execute the job.
     *
     * @param GhlRepository $ghlRepository
     * @return void
     */
    public function handle(GhlRepository $ghlRepository): void
    {

        if (empty($this->whiteboard) || $this->whiteboard == '<p><br></p>' || $this->whiteboard == '<p><br><\/p>') {
            //$this->updateLog(LogModel::STATUS_SUCCESS, 'Note skipped: No whiteboard data provided.');
            return;
        }

        // $this->whiteboard = replaceBrWithNewline($this->whiteboard);

        try {

            // For new contacts, create note directly
            if ($this->isNewContact) {
                $this->createNote($ghlRepository, 'new contact');
                return;
            }

            // For existing contacts, check for duplicates
            $response = $ghlRepository->getContactNotes($this->contactId, $this->locationId, true);

            if (! $response || ! property_exists($response, 'notes')) {
                throw new \Exception('Notes retrival failed - Reason' . json_encode($response));
            }

            $existingNotes = (array) $response->notes ?? null;

            if ($existingNotes && count($existingNotes) > 0) {

                $isDuplicate = collect($existingNotes)->contains(fn($note) => $this->textChange($note->body ?? '') === $this->textChange($this->whiteboard));

                if ($isDuplicate) {
                    // $this->updateLog(LogModel::STATUS_SUCCESS, 'Note skipped: Duplicate found.');
                    return;
                }
            }

            // Create note for existing contact
            $this->createNote($ghlRepository, 'existing contact');

        } catch (\Exception $e) {
            $this->updateLog(LogModel::STATUS_FAILED, "Note creation failed: {$e->getMessage()}", [
                'contactId'  => $this->contactId,
                'whiteboard' => $this->whiteboard,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    protected function textChange(string $context): string
    {
        return trim(strtolower(str_replace([' '], '', $context)));
    }
    /**
     * Create a note in GHL and update the log.
     *
     * @param GhlRepository $ghlRepository
     * @param string $context
     * @return void
     * @throws \Exception
     */

    protected function createNote(GhlRepository $ghlRepository, string $context): void
    {
        $noteData = [
            'body' => $this->whiteboard,
        ];

        $newNote = $ghlRepository->createContactNote($this->contactId, $this->locationId, $noteData, true);

        // Use object property access
        if (! isset($newNote->note->id)) {
            throw new \Exception('Failed to create note: ' . json_encode($newNote));
        }

        $note = $newNote->note;

        $this->updateLog(
            LogModel::STATUS_SUCCESS,
            "Note created successfully for $context, Note ID: {$note->id}",
            ['note' => (array) $note]// cast to array for structured logging (optional)
        );
    }

    /**
     * Update the log with the given status, message, and additional response data.
     *
     * @param string $status
     * @param string $message
     * @param array $additionalResponse
     * @return void
     */
    protected function updateLog(string $status, string $message, array $additionalResponse = []): void
    {
        $this->log->update([
            'status'   => $status,
            'message'  => "{$this->log->message} | $message",
            'response' => array_merge($this->log->response ?? [], $additionalResponse),
        ]);
    }
}
