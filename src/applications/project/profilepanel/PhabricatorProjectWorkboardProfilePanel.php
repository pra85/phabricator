<?php

final class PhabricatorProjectWorkboardProfilePanel
  extends PhabricatorProfilePanel {

  const PANELKEY = 'project.workboard';

  public function getPanelTypeName() {
    return pht('Project Workboard');
  }

  private function getDefaultName() {
    return pht('Workboard');
  }

  public function getDisplayName(
    PhabricatorProfilePanelConfiguration $config) {
    $name = $config->getPanelProperty('name');

    if (strlen($name)) {
      return $name;
    }

    return $this->getDefaultName();
  }

  public function buildEditEngineFields(
    PhabricatorProfilePanelConfiguration $config) {
    return array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setPlaceholder($this->getDefaultName())
        ->setValue($config->getPanelProperty('name')),
    );
  }

  protected function newNavigationMenuItems(
    PhabricatorProfilePanelConfiguration $config) {
    $viewer = $this->getViewer();

    // Workboards are only available if Maniphest is installed.
    $class = 'PhabricatorManiphestApplication';
    if (!PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
      return array();
    }

    $project = $config->getProfileObject();

    $columns = id(new PhabricatorProjectColumnQuery())
      ->setViewer($viewer)
      ->withProjectPHIDs(array($project->getPHID()))
      ->execute();
    if ($columns) {
      $icon = 'fa-columns';
    } else {
      $icon = 'fa-columns grey';
    }

    $id = $project->getID();
    $href = "/project/board/{$id}/";
    $name = $this->getDisplayName($config);

    $item = $this->newItem()
      ->setHref($href)
      ->setName($name)
      ->setIcon($icon);

    return array(
      $item,
    );
  }

}
