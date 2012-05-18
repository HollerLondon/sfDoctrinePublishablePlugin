<?php
/**
 * Adds ability to select records based on their publish date. Adds
 * two new columns 'published_at' and 'publish_until'.
 */
class Publishable extends Doctrine_Template
{

  protected $_options = array(
    'published_at' => array(
      'name'          =>  'published_at',
      'alias'         =>  null,
      'type'          =>  'timestamp',
      'format'        =>  'Y-m-d H:i:s',
      'disabled'      =>  false,
      'expression'    =>  false,
      'options'       =>  array('notnull' => false)
    ),
    'publish_until' => array(
      'name'          =>  'publish_until',
      'alias'         =>  null,
      'type'          =>  'timestamp',
      'format'        =>  'Y-m-d H:i:s',
      'disabled'      =>  false,
      'expression'    =>  false,
      'onInsert'      =>  true,
      'options'       =>  array('notnull' => false)
    )
  );

  /**
   *
   */
  public function setTableDefinition()
  {
    $this->hasColumn('is_draft', 'boolean', true);
    
    if (!$this->_options['published_at']['disabled'])
    {
      $name = $this->_options['published_at']['name'];
      if ($this->_options['published_at']['alias'])
      {
        $name .= ' as ' . $this->_options['published_at']['alias'];
      }
      $this->hasColumn($name, $this->_options['published_at']['type'], null, $this->_options['published_at']['options']);
    }

    if (!$this->_options['publish_until']['disabled'])
    {
      $name = $this->_options['publish_until']['name'];
      if ($this->_options['publish_until']['alias'])
      {
        $name .= ' as ' . $this->_options['publish_until']['alias'];
      }
      $this->hasColumn($name, $this->_options['publish_until']['type'], null, $this->_options['publish_until']['options']);
    }

    // Make sure we use the field names set above
    $this->index(sprintf('%s_is_draft_idx',     $this->getTable()->getTableName()), array('fields' => array('is_draft')));
    $this->index(sprintf('%s_published_at_idx', $this->getTable()->getTableName()), array('fields' => array($this->_options['published_at']['name'])));
    $this->index(sprintf('%s_publish_until_idx',$this->getTable()->getTableName()), array('fields' => array($this->_options['publish_until']['name'])));

    $this->addListener(new PublishableListener($this->_options));
  }

  /**
   * @return boolean
   */
  public function isPublished()
  {
    $object = $this->getInvoker();

    if ($object['is_draft'])
    {
      return false; 
    }
    
    // not yet published
    $published_at = $this->_options['published_at']['name'];
    if (strtotime($object[$published_at]) > time())
    {
        return false;
    }

    // publish_until might not be specified, only act if it is
    $publish_until = $this->_options['publish_until']['name'];
    if ($object['publish_until'])
    {
        return (strtotime($object[$publish_until]) >= time());
    }

    return true;
  }
}
