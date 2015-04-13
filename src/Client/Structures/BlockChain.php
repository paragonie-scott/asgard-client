<?php
namespace ParagonIE\AsgardClient\Structures;

class BlockChain implements \Iterator
{
    private $blocks = [];
    private $numBlocks = 0;
    private $iVal;
    
    public function __construct($blocks = [])
    {
        foreach ($blocks as $block) {
            $this->append($block);
        }
    }
    
    public function append(Block $block)
    {
        if ($this->numBlocks > 0) {
            $last = $this->numBlocks - 1;
            
            $this->blocks[$last]->setNextHash(
                $block->getHash()
            );
        }
        $this->numBlocks++;
        $this->blocks []= $block;
    }

	/**
	 * Return the current element
     * 
     * @return \ParagonIE\AsgardClient\Structures\Block
	 */
    public function current()
    {
        return $this->blocks[$this->iVal];
    }

	/**
	 * Return the key of the current element
     * 
     * @return int
	 */
    public function key()
    {
        return $this->iVal;
    }

	/**
	 * Move forward to next element
     * 
     * @return \ParagonIE\AsgardClient\Structures\Block
	 */
    public function next()
    {
        return $this->blocks[$this->iVal++];
    }

	/**
	 * Rewind the Iterator to the first element
	 */
    public function rewind()
    {
        $this->iVal = 0;
    }

    /**
     * Checks if current position is valid
     * 
     * @return boolean
     */
    public function valid()
    {
        return isset($this->blocks[$this->iVal]);
    }
}
