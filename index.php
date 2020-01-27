<?php

use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

include_once "vendor/autoload.php";

function findNewBase($template, $list = [], $currentBase = null) {
    $result = 'base';
    $found = $currentBase == null; //if null, took the first one
    foreach($list as $key => $path) {
        if($key == $currentBase) {
            $found = true;
        }
        else if ($found && file_exists($path . '/' . $template)) {
            $result = $key;
            break;
        }
    }
        
    return $result;
}

final class ExtTokenParser extends AbstractTokenParser {
    /**
     * @var Parser
     */
    protected $parser;

    private $list = [];

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function getTag(): string
    {
        return 'base_extends';
    }

    /**
     * @return Node
     */
    public function parse(Token $token)
    {       
        $stream = $this->parser->getStream();
        $source = $stream->getSourceContext()->getName();
        $template = $stream->next()->getValue();

        $parts = preg_split("/\//i", preg_replace("/@/", '', $source));
        $newBase = findNewBase($template, $this->list, $parts[0]);        
        $parent = '@' . $newBase . '/' . $template;

        $stream->next();

        $stream->injectTokens([
            new Token(Token::BLOCK_START_TYPE, '', 2),
            new Token(Token::NAME_TYPE, 'extends', 2),
            new Token(Token::STRING_TYPE, $parent, 2),
            new Token(Token::BLOCK_END_TYPE, '', 2),
        ]);

        return new Node();
    }
}

final class IncTokenParser extends AbstractTokenParser {
    /**
     * @var Parser
     */
    protected $parser;

    private $list = [];

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function getTag(): string
    {
        return 'base_include';
    }

    /**
     * @return Node
     */
    public function parse(Token $token)
    {       
        $stream = $this->parser->getStream();
        $source = $stream->getSourceContext()->getName();
        $template = $stream->next()->getValue();

        var_dump($source);

        //$parts = preg_split("/\//i", preg_replace("/@/", '', $source));
        $newBase = findNewBase($template, $this->list, null);        
        $parent = '@' . $newBase . '/' . $template;

        $stream->next();

        $stream->injectTokens([
            new Token(Token::BLOCK_START_TYPE, '', 2),
            new Token(Token::NAME_TYPE, 'include', 2),
            new Token(Token::STRING_TYPE, $parent, 2),
            new Token(Token::BLOCK_END_TYPE, '', 2),
        ]);

        return new Node();
    }
}


$list = [
    'plugin2' => 'templates/path3',
    'plugin1' => 'templates/path2',
    'base' => 'templates/path1'
];

$loader = new \Twig\Loader\FilesystemLoader();
foreach($list as $plugin => $path) {
    $loader->addPath($path, $plugin); //plugin as namespace
}

$twig = new \Twig\Environment($loader);
$twig->addTokenParser(new ExtTokenParser($list));
$twig->addTokenParser(new IncTokenParser($list));
echo $twig->render('@' . findNewBase('index.twig', $list) . '/index.twig', []);