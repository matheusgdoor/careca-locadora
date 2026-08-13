<?php
namespace App\Services\Rentals;
use App\Models\RentalContract;
use App\Models\RentalContractSignatureRequest;
use Barryvdh\DomPDF\Facade\Pdf;
final class RentalContractDocumentService
{
    private const SNAPSHOT_VERSION = 2;
    public function data(RentalContract $contract, ?RentalContractSignatureRequest $signatureRequest = null): array
    {
        $contract->loadMissing(['customer','authorizedContact','responsibleUser','company','branch','items.asset.category']);
        $signatureRequest ??= $contract->signatureRequests()->where('status','signed')->latest('signed_at')->first();
        $logoPath=public_path('images/careca-locadora-logo.png');
        $logo=is_file($logoPath)?'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)):null;
        return ['contract'=>$contract,'logo'=>$logo,'signatureRequest'=>$signatureRequest,'signatureImage'=>$this->signatureDataUri($signatureRequest)];
    }
    public function liveHtml(RentalContract $contract): string { return view('pdf.rental-contract',$this->data($contract,null))->render(); }
    public function liveHash(RentalContract $contract): string { return hash('sha256',$this->liveHtml($contract)); }
    public function freeze(RentalContractSignatureRequest $signatureRequest,bool $force=false): RentalContractSignatureRequest
    {
        $metadata=$signatureRequest->metadata??[];
        if(!$force && filled($metadata['document_html']??null)) return $signatureRequest;
        $signatureRequest->loadMissing('contract'); $html=$this->liveHtml($signatureRequest->contract);
        $metadata['document_snapshot_version']=self::SNAPSHOT_VERSION; $metadata['document_html']=$html; $metadata['document_frozen_at']=now()->toIso8601String();
        $signatureRequest->forceFill(['document_hash'=>hash('sha256',$html),'metadata'=>$metadata])->save();
        return $signatureRequest->fresh();
    }
    public function frozenHtml(RentalContractSignatureRequest $signatureRequest): string { $signatureRequest=$this->freeze($signatureRequest); return (string)data_get($signatureRequest->metadata,'document_html',''); }
    public function frozenHash(RentalContractSignatureRequest $signatureRequest): string { return hash('sha256',$this->frozenHtml($signatureRequest)); }
    public function bytes(RentalContract $contract,?RentalContractSignatureRequest $signatureRequest=null): string
    {
        if($signatureRequest){$signatureRequest=$this->freeze($signatureRequest);$html=$signatureRequest->status==='signed'?$this->signedHtml($signatureRequest):$this->frozenHtml($signatureRequest);return Pdf::loadHTML($html)->setPaper('a4')->output();}
        return Pdf::loadView('pdf.rental-contract',$this->data($contract))->setPaper('a4')->output();
    }
    public function hash(RentalContract $contract,?RentalContractSignatureRequest $signatureRequest=null): string { return $signatureRequest?$this->frozenHash($signatureRequest):$this->liveHash($contract); }
    public function signedContentHash(RentalContractSignatureRequest $signatureRequest): string { return hash('sha256',$this->signedHtml($signatureRequest)); }
    public function filename(RentalContract $contract): string { return 'contrato-'.preg_replace('/[^A-Za-z0-9_-]+/','-',$contract->number).'.pdf'; }
    private function signedHtml(RentalContractSignatureRequest $signatureRequest): string
    {
        $base=$this->frozenHtml($signatureRequest);$signatureRequest->loadMissing('contract');$certificate=view('pdf.rental-contract-signature-certificate',['signatureRequest'=>$signatureRequest,'signatureImage'=>$this->signatureDataUri($signatureRequest)])->render();
        return str_contains($base,'</body>')?str_replace('</body>',$certificate.'</body>',$base):$base.$certificate;
    }
    private function signatureDataUri(?RentalContractSignatureRequest $signatureRequest): ?string
    {
        if(!$signatureRequest) return null; $fromMetadata=data_get($signatureRequest->metadata,'signature_data_url'); if(filled($fromMetadata)) return (string)$fromMetadata; return RentalContractStorage::dataUri($signatureRequest->signature_path);
    }
}
