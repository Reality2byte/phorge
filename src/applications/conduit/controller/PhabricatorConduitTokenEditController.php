<?php

final class PhabricatorConduitTokenEditController
  extends PhabricatorConduitController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    if ($id) {
      $token = id(new PhabricatorConduitTokenQuery())
        ->setViewer($viewer)
        ->withIDs(array($id))
        ->withExpired(false)
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->executeOne();
      if (!$token) {
        return new Aphront404Response();
      }

      $object = $token->getObject();

      $is_new = false;
      $title = pht('View API Token');
    } else {
      $object = id(new PhabricatorObjectQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($request->getStr('objectPHID')))
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->executeOne();
      if (!$object) {
        return new Aphront404Response();
      }

      $token = PhabricatorConduitToken::initializeNewToken(
        $object->getPHID(),
        PhabricatorConduitToken::TYPE_STANDARD);

      $is_new = true;
      $title = pht('Generate API Token');
      $submit_button = pht('Generate Token');
    }

    $panel_uri = id(new PhabricatorConduitTokensSettingsPanel())
      ->setViewer($viewer)
      ->setUser($object)
      ->getPanelURI();

    id(new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
      $viewer,
      $request,
      $panel_uri);

    if ($request->isFormPost()) {
      $token->save();

      if ($is_new) {
        $token_uri = '/conduit/token/edit/'.$token->getID().'/';
      } else {
        $token_uri = $panel_uri;
      }

      return id(new AphrontRedirectResponse())->setURI($token_uri);
    }

    $dialog = $this->newDialog()
      ->setTitle($title)
      ->addHiddenInput('objectPHID', $object->getPHID());

    if ($is_new) {
      $dialog
        ->appendParagraph(pht('Generate a new API token?'))
        ->addSubmitButton($submit_button)
        ->addCancelButton($panel_uri);
    } else {
      if ($token->getTokenType() === PhabricatorConduitToken::TYPE_CLUSTER) {
        $dialog->appendChild(
          pht(
            'This token is automatically generated, and used to make '.
            'requests between nodes in a cluster. You can not use this '.
            'token in external applications.'));
      } else {
        $form = id(new AphrontFormView())
          ->setUser($viewer)
          ->appendChild(
            id(new AphrontFormTextControl())
              ->setLabel(pht('Token'))
              ->setReadOnly(true)
              ->setValue($token->getToken()));

        $dialog->appendForm($form);
      }

      $dialog->addCancelButton($panel_uri, pht('Done'));
    }

    return $dialog;
  }

}
