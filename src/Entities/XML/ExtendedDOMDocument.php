<?php
/*
 * Created on   : Thu Jan 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExtendedDOMDocument.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XML;

use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\XmlHelper;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

/**
 * Erweiterte DOMDocument-Klasse mit XPath-Hilfsmethoden.
 * 
 * Bietet erweiterte Funktionalität für XML-Parsing:
 * - Automatische Namespace-Erkennung und -Registrierung
 * - XPath-Hilfsmethoden (xpathString, xpathStringWithFallback)
 * - Node-Suche (findNode, findRequiredNode, findNodes)
 * - Datentyp-Konvertierung (parseAmount, parseDateTime, parseAmountWithCurrency)
 * - Integration mit CommonToolkit XML-Entities
 * - PSR-3 konformes Logging
 * 
 * Verwendung:
 * ```php
 * $doc = ExtendedDOMDocumentParser::fromString($xmlContent);
 * $value = $doc->xpathString('//ns:Element/ns:Value');
 * $nodes = $doc->findNodes('//ns:Items/ns:Item');
 * ```
 * 
 * @see ExtendedDOMDocumentParser Factory-Methoden zum Erstellen
 */
class ExtendedDOMDocument extends DOMDocument {
    use ErrorLog;

    protected DOMXPath $xpath;
    protected ?string $namespace = null;
    protected string $nsPrefix = '';

    /** @var Document|null Gecachtes CommonToolkit Document */
    private ?Document $xmlDocument = null;

    // =========================================================================
    // INITIALISIERUNG
    // =========================================================================

    /**
     * Initialisiert das Dokument aus einem XML-String.
     * 
     * @param string $xmlContent Der XML-Inhalt
     * @throws RuntimeException Bei ungültigem XML
     * @internal Wird vom ExtendedDOMDocumentParser aufgerufen
     */
    public function initializeFromString(string $xmlContent): void {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$this->loadXML($xmlContent)) {
            $errors = XmlHelper::getLibXmlErrors();
            $errorMessage = "Ungültiges XML-Dokument: " . ($errors[0] ?? 'Unbekannter Fehler');
            self::logError($errorMessage);
            throw new RuntimeException($errorMessage);
        }

        libxml_clear_errors();
        $this->initializeXPath();
    }

    /**
     * Initialisiert XPath mit automatischer Namespace-Erkennung.
     * 
     * @internal Wird vom ExtendedDOMDocumentParser aufgerufen
     */
    public function initializeXPath(): void {
        $this->xpath = new DOMXPath($this);
        $this->namespace = $this->detectNamespace();

        if (!empty($this->namespace)) {
            $this->registerXPathNamespace($this->getNamespacePrefix(), $this->namespace);
            $this->nsPrefix = $this->getNamespacePrefix() . ':';
        }
    }

    /**
     * Erkennt den Namespace des XML-Dokuments.
     * Kann von Unterklassen überschrieben werden.
     * 
     * @return string|null Der erkannte Namespace oder null
     */
    protected function detectNamespace(): ?string {
        $root = $this->documentElement;
        if (!$root) {
            return null;
        }

        // Prüfe namespaceURI des Root-Elements
        if (!empty($root->namespaceURI)) {
            return $root->namespaceURI;
        }

        // Prüfe xmlns-Attribut
        if ($root->hasAttribute('xmlns')) {
            return $root->getAttribute('xmlns');
        }

        return null;
    }

    /**
     * Gibt den zu verwendenden Namespace-Prefix zurück.
     * Kann von Unterklassen überschrieben werden.
     * 
     * @return string Der Namespace-Prefix (z.B. 'ns', 'p', 'camt')
     */
    public function getNamespacePrefix(): string {
        return 'ns';
    }

    /**
     * Registriert einen Namespace im XPath.
     * 
     * @param string $prefix Der Namespace-Prefix
     * @param string $namespace Die Namespace-URI
     */
    public function registerXPathNamespace(string $prefix, string $namespace): void {
        $this->xpath->registerNamespace($prefix, $namespace);
    }

    // =========================================================================
    // COMMONTOOLKIT INTEGRATION
    // =========================================================================

    /**
     * Gibt das CommonToolkit XML-Document zurück.
     */
    public function toXmlDocument(): Document {
        if ($this->xmlDocument === null) {
            $this->xmlDocument = Document::fromDomDocument($this);
        }
        return $this->xmlDocument;
    }

    /**
     * Gibt das Root-Element als CommonToolkit Element zurück.
     */
    public function toXmlElement(): Element {
        return $this->toXmlDocument()->getRootElement();
    }

    /**
     * Gibt das Namespace-Mapping für XPath-Queries zurück.
     * 
     * @return array<string, string>
     */
    public function getNamespaceMapping(): array {
        if ($this->namespace === null) {
            return [];
        }

        $prefixName = rtrim($this->getNamespacePrefix(), ':');
        return [$prefixName => $this->namespace];
    }

    // =========================================================================
    // XPATH HELPER METHODEN
    // =========================================================================

    /**
     * Evaluiert einen XPath-Ausdruck und gibt einen String oder null zurück.
     * Fügt automatisch string(...) hinzu, wenn nicht vorhanden.
     * 
     * @param string $expression XPath-Ausdruck
     * @param DOMNode|null $context Kontext-Node (optional)
     * @return string|null Ergebnis oder null wenn leer
     */
    public function xpathString(string $expression, ?DOMNode $context = null): ?string {
        // Automatisch string() hinzufügen, wenn nicht vorhanden
        if (!str_starts_with($expression, 'string(')) {
            $expression = "string({$expression})";
        }

        $result = $context !== null
            ? $this->xpath->evaluate($expression, $context)
            : $this->xpath->evaluate($expression);

        return !empty($result) ? (string)$result : null;
    }

    /**
     * Evaluiert einen XPath-Ausdruck mit Fallback-Alternativen.
     * 
     * @param array<string> $expressions Liste von XPath-Ausdrücken
     * @param DOMNode|null $context Kontext-Node (optional)
     * @return string|null Erstes nicht-leeres Ergebnis oder null
     */
    public function xpathStringWithFallback(array $expressions, ?DOMNode $context = null): ?string {
        foreach ($expressions as $expression) {
            $result = $this->xpathString($expression, $context);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Evaluiert einen XPath-Ausdruck und gibt das Ergebnis zurück.
     * 
     * @param string $expression XPath-Ausdruck
     * @param DOMNode|null $context Kontext-Node (optional)
     * @return mixed Das Evaluierungsergebnis
     */
    public function xpathEvaluate(string $expression, ?DOMNode $context = null): mixed {
        return $context !== null
            ? $this->xpath->evaluate($expression, $context)
            : $this->xpath->evaluate($expression);
    }

    /**
     * Findet einen Node via XPath.
     * 
     * @param string $expression XPath-Ausdruck
     * @param DOMNode|null $context Kontext-Node (optional)
     * @return DOMNode|null Der gefundene Node oder null
     */
    public function findNode(string $expression, ?DOMNode $context = null): ?DOMNode {
        $result = $context !== null
            ? $this->xpath->query($expression, $context)
            : $this->xpath->query($expression);

        return ($result && $result->length > 0) ? $result->item(0) : null;
    }

    /**
     * Findet einen erforderlichen Node via XPath.
     * 
     * @param string $expression XPath-Ausdruck
     * @param string $errorMessage Fehlermeldung wenn nicht gefunden
     * @param DOMNode|null $context Kontext-Node (optional)
     * @return DOMNode Der gefundene Node
     * @throws RuntimeException Wenn Node nicht gefunden
     */
    public function findRequiredNode(string $expression, string $errorMessage, ?DOMNode $context = null): DOMNode {
        $node = $this->findNode($expression, $context);
        if ($node === null) {
            self::logError($errorMessage);
            throw new RuntimeException($errorMessage);
        }
        return $node;
    }

    /**
     * Findet alle Nodes via XPath.
     * 
     * @param string $expression XPath-Ausdruck
     * @param DOMNode|null $context Kontext-Node (optional)
     * @return DOMNodeList<DOMNode> Die gefundenen Nodes
     */
    public function findNodes(string $expression, ?DOMNode $context = null): DOMNodeList {
        return $context !== null
            ? $this->xpath->query($expression, $context)
            : $this->xpath->query($expression);
    }

    // =========================================================================
    // DATENTYP-KONVERTIERUNG
    // =========================================================================

    /**
     * Parst einen Betrag aus einem String.
     * 
     * @param string|null $amountStr Betrags-String
     * @return float Betrag als Float
     */
    public function parseAmount(?string $amountStr): float {
        if ($amountStr === null || $amountStr === '') {
            return 0.0;
        }
        return (float) str_replace(',', '.', $amountStr);
    }

    /**
     * Parst einen Betrag mit Währung aus einem Element mit Ccy-Attribut.
     * 
     * @param string $amountPath XPath zum Betrags-Element
     * @param DOMNode $context Kontext-Node
     * @param CurrencyCode $default Standard-Währung
     * @return array{amount: float, currency: CurrencyCode}
     */
    public function parseAmountWithCurrency(
        string $amountPath,
        DOMNode $context,
        CurrencyCode $default = CurrencyCode::Euro
    ): array {
        $amtNode = $this->xpath->query($amountPath, $context)->item(0);

        $amount = 0.0;
        $currency = $default;

        if ($amtNode instanceof DOMElement) {
            $amount = $this->parseAmount($amtNode->textContent);
            $currencyStr = $amtNode->getAttribute('Ccy') ?: 'EUR';
            $currency = CurrencyCode::tryFrom($currencyStr) ?? $default;
        }

        return ['amount' => $amount, 'currency' => $currency];
    }

    /**
     * Parst einen Betrag mit Währung aus einem CommonToolkit Element.
     * 
     * @param Element $element Element mit Ccy-Attribut
     * @param CurrencyCode $default Standard-Währung
     * @return array{amount: float, currency: CurrencyCode}
     */
    public function parseAmountFromElement(
        Element $element,
        CurrencyCode $default = CurrencyCode::Euro
    ): array {
        $amount = $this->parseAmount($element->getValue());
        $currencyStr = $element->getAttributeValue('Ccy', 'EUR');
        $currency = CurrencyCode::tryFrom($currencyStr) ?? $default;

        return ['amount' => $amount, 'currency' => $currency];
    }

    /**
     * Parst einen DateTime-String zu DateTimeImmutable.
     * 
     * @param string|null $dateTimeStr Der DateTime-String
     * @param DateTimeImmutable|null $default Standardwert wenn leer
     * @return DateTimeImmutable|null
     */
    public function parseDateTime(?string $dateTimeStr, ?DateTimeImmutable $default = null): ?DateTimeImmutable {
        if (empty($dateTimeStr)) {
            return $default;
        }
        return new DateTimeImmutable($dateTimeStr);
    }

    /**
     * Konvertiert leere Strings zu null.
     * 
     * @param string $value Der String
     * @return string|null Null wenn leer, sonst der String
     */
    public function emptyToNull(string $value): ?string {
        return $value !== '' ? $value : null;
    }

    // =========================================================================
    // GETTER
    // =========================================================================

    /**
     * Gibt das XPath-Objekt zurück.
     */
    public function getXPath(): DOMXPath {
        return $this->xpath;
    }

    /**
     * Gibt den aktuellen Namespace zurück.
     */
    public function getNamespace(): ?string {
        return $this->namespace;
    }

    /**
     * Gibt den aktuellen Namespace-Prefix zurück.
     */
    public function getNsPrefix(): string {
        return $this->nsPrefix;
    }

    /**
     * Bereinigt libxml-Ressourcen.
     */
    public function cleanup(): void {
        libxml_clear_errors();
    }
}
