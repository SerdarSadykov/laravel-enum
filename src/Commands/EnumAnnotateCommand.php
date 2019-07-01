<?php

namespace BenSampo\Enum\Commands;

use BenSampo\Enum\Enum;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Static_;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Zend\Code\Generator\DocBlock\Tag\MethodTag;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;
use Zend\Code\Generator\DocBlock\Tag\TagInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Reflection\DocBlockReflection;

class EnumAnnotateCommand extends AbstractAnnotationCommand
{
    const DEFAULT_SCAN_FOLDER = 'Enums';
    const PARENT_CLASS = Enum::class;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'enum:annotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate annotations for enum classes';

    /**
     * Apply annotations to a reflected class
     *
     * @param ReflectionClass $reflectionClass
     *
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function annotate(ReflectionClass $reflectionClass)
    {
        $docBlock = DocBlockGenerator::fromArray([]);
        $originalDocBlock =  null;

        if (strlen($reflectionClass->getDocComment()) !== 0) {
            $originalDocBlock = DocBlockGenerator::fromReflection(new DocBlockReflection($reflectionClass));
            $docBlock->setShortDescription($originalDocBlock->getShortDescription());
        }

        $this->updateClassDocblock($reflectionClass, $this->getDocBlock($reflectionClass));
    }

    protected function getDocblockTags(array $originalTags, ReflectionClass $reflectionClass): array
    {
        $constants = $reflectionClass->getConstants();

        $existingTags = array_filter($originalTags, function (TagInterface $tag) use ($constants) {
            return !$tag instanceof MethodTag || !in_array($tag->getMethodName(), $constants, true);
        });

        return collect($constants)
            ->map(function ($value, $constantName) {
                return new MethodTag($constantName, ['static'], null, true);
            })
            ->merge($existingTags)
            ->toArray();
    }

    protected function getClassFinder(): Finder
    {
        $finder = new Finder();
        $scanPath = $this->option('folder') ?? app_path(self::DEFAULT_SCAN_FOLDER);

        return $finder->files()->in($scanPath)->name('*.php');
    }
}
