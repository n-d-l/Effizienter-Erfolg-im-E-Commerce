<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\Element;

use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;


final class AdditionalInformation implements QrCodeableInterface
{

    public const TRAILER_EPD = 'EPD';

    /**
     * Unstructured information can be used to indicate the payment purpose
     * or for additional textual information about payments with a structured reference.
     */
    private ?string $message;

    /**
     * Bill information contains coded information for automated booking of the payment.
     * The data is not forwarded with the payment.
     */
    private ?string $billInformation;

    private function __construct(
        ?string $message,
        ?string $billInformation
    ) {
        $this->message = $message;
        $this->billInformation = $billInformation;
    }

    public static function create(
        ?string $message,
        ?string $billInformation = null
    ): self {
        return new self($message, $billInformation);
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getBillInformation(): ?string
    {
        return $this->billInformation;
    }

    public function getFormattedString(): ?string
    {
        $string = $this->getMessage();
        if ($this->getBillInformation()) {
            $string .= "\n".$this->getBillInformation();
        }

        return $string;
    }

    public function getQrCodeData(): array
    {
        return [
            $this->getMessage(),
            self::TRAILER_EPD,
            $this->getBillInformation()
        ];
    }

}
