<?php
class PublishableListener extends Doctrine_Record_Listener
{
  /**
   * Make sure passed through options are set - as no global constructor
   * @param array $options
   */
  public function __construct($options)
  {
    foreach ($options as $name => $value)
    {
      $this->setOption($name, $value);
    }
  }
  
  
  /**
   * This method adds a where clause that means only 'published' records are returned:
   * those whose publish_until is less than the current time, and whose published_at is in the past (or now). 
   * 
   * @param Doctrine_Event $event
   * @return void
   */
  public function preDqlSelect(Doctrine_Event $event)
  {
    // only take action if the plugin's predql callback is enabled
    if (!Doctrine_Manager::getInstance()->getAttribute('publishable_enable_predql'))
    {
      return;
    }
    
    $params = $event->getParams();
    $query  = $event->getQuery();
    
    $published_at_options = $this->getOption('published_at');
    $publish_until_options = $this->getOption('publish_until');
    
    // Make sure we use names set in options
    $field1 = $params['alias'] . '.' . $published_at_options['name'];
    $field2 = $params['alias'] . '.' . $publish_until_options['name'];
    $field3 = $params['alias'] . '.is_draft';

    if((!$query->isSubquery() || ($query->isSubquery() && $query->contains(' ' . $params['alias'] . ' '))))
    {
      $clause = "((%until% IS NULL AND %at% <= NOW()) OR (NOW() BETWEEN %at% AND %until%)) AND (%is_draft% IS NULL OR %is_draft% != %true%)";
      $clause = strtr($clause,array(
        '%until%'    => $field2,
        '%at%'       => $field1,
        '%is_draft%' => $field3,
        '%true%'     => $query->getConnection()->convertBooleans(true),
      ));
      
      $query->addPendingJoinCondition($params['alias'], $clause);
    }
  }  
}
