<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloadebab9743706eeeb9ce22bdce9d09d6e6($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'diff_match_patch' => '/diff_match_patch/diff_match_patch.php',
            'patch_obj' => '/diff_match_patch/diff_match_patch.php',
            'pullrequestplugin' => '/pullrequestPlugin.class.php',
            'tuleap\\pullrequest\\additionalactionspresenter' => '/PullRequest/AdditionalActionsPresenter.php',
            'tuleap\\pullrequest\\additionalhelptextpresenter' => '/PullRequest/AdditionalHelpTextPresenter.php',
            'tuleap\\pullrequest\\additionalinfopresenter' => '/PullRequest/AdditionalInfoPresenter.php',
            'tuleap\\pullrequest\\comment\\comment' => '/PullRequest/Comment/Comment.php',
            'tuleap\\pullrequest\\comment\\dao' => '/PullRequest/Comment/Dao.php',
            'tuleap\\pullrequest\\comment\\factory' => '/PullRequest/Comment/Factory.php',
            'tuleap\\pullrequest\\comment\\paginatedcomments' => '/PullRequest/Comment/PaginatedComments.php',
            'tuleap\\pullrequest\\dao' => '/PullRequest/Dao.php',
            'tuleap\\pullrequest\\exception\\pullrequestalreadyexistsexception' => '/PullRequest/Exception/PullRequestAlreadyExistsException.php',
            'tuleap\\pullrequest\\exception\\pullrequestcannotbeabandoned' => '/PullRequest/Exception/PullRequestCannotBeAbandoned.php',
            'tuleap\\pullrequest\\exception\\pullrequestcannotbecreatedexception' => '/PullRequest/Exception/PullRequestCannotBeCreatedException.php',
            'tuleap\\pullrequest\\exception\\pullrequestcannotbemerged' => '/PullRequest/Exception/PullRequestCannotBeMerged.php',
            'tuleap\\pullrequest\\exception\\pullrequestnotcreatedexception' => '/PullRequest/Exception/PullRequestNotCreatedException.php',
            'tuleap\\pullrequest\\exception\\pullrequestnotfoundexception' => '/PullRequest/Exception/PullRequestNotFoundException.php',
            'tuleap\\pullrequest\\exception\\pullrequestrepositorymigratedongerritexception' => '/PullRequest/Exception/PullRequestRepositoryMigratedOnGerritException.php',
            'tuleap\\pullrequest\\exception\\unknownbranchnameexception' => '/PullRequest/Exception/UnknownBranchNameException.php',
            'tuleap\\pullrequest\\exception\\unknownreferenceexception' => '/PullRequest/Exception/UnknownReferenceException.php',
            'tuleap\\pullrequest\\factory' => '/PullRequest/Factory.php',
            'tuleap\\pullrequest\\fileunidiff' => '/PullRequest/FileUniDiff.php',
            'tuleap\\pullrequest\\fileunidiffbuilder' => '/PullRequest/FileUniDiffBuilder.php',
            'tuleap\\pullrequest\\gitexec' => '/PullRequest/GitExec.php',
            'tuleap\\pullrequest\\inlinecomment\\dao' => '/PullRequest/InlineComment/Dao.php',
            'tuleap\\pullrequest\\inlinecomment\\inlinecomment' => '/PullRequest/InlineComment/InlineComment.php',
            'tuleap\\pullrequest\\inlinecomment\\inlinecommentupdater' => '/PullRequest/InlineComment/InlineCommentUpdater.php',
            'tuleap\\pullrequest\\plugindescriptor' => '/PullRequestPluginDescriptor.class.php',
            'tuleap\\pullrequest\\plugininfo' => '/PullRequestPluginInfo.class.php',
            'tuleap\\pullrequest\\pullrequest' => '/PullRequest/PullRequest.php',
            'tuleap\\pullrequest\\pullrequestcloser' => '/PullRequest/PullRequestCloser.php',
            'tuleap\\pullrequest\\pullrequestcreator' => '/PullRequest/PullRequestCreator.php',
            'tuleap\\pullrequest\\pullrequestpresenter' => '/PullRequest/PullRequestPresenter.php',
            'tuleap\\pullrequest\\pullrequestupdater' => '/PullRequest/PullRequestUpdater.php',
            'tuleap\\pullrequest\\rest\\resourcesinjector' => '/PullRequest/REST/ResourcesInjector.class.php',
            'tuleap\\pullrequest\\rest\\v1\\commentpostrepresentation' => '/PullRequest/REST/v1/CommentPOSTRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\commentrepresentation' => '/PullRequest/REST/v1/CommentRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\paginatedcommentsrepresentations' => '/PullRequest/REST/v1/PaginatedCommentsRepresentations.php',
            'tuleap\\pullrequest\\rest\\v1\\paginatedcommentsrepresentationsbuilder' => '/PullRequest/REST/v1/PaginatedCommentsRepresentationsBuilder.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestfilerepresentation' => '/PullRequest/REST/v1/PullRequestFileRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestfilerepresentationfactory' => '/PullRequest/REST/v1/PullRequestFileRepresentationFactory.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestfileunidiffrepresentation' => '/PullRequest/REST/v1/PullRequestFileUniDiffRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestinlinecommentpostrepresentation' => '/PullRequest/REST/v1/PullRequestInlineCommentPOSTRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestinlinecommentrepresentation' => '/PullRequest/REST/v1/PullRequestInlineCommentRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestinlinecommentrepresentationbuilder' => '/PullRequest/REST/v1/PullRequestInlineCommentRepresentationBuilder.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestlineunidiffrepresentation' => '/PullRequest/REST/v1/PullRequestLineUniDiffRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestpatchrepresentation' => '/PullRequest/REST/v1/PullRequestPATCHRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestpostrepresentation' => '/PullRequest/REST/v1/PullRequestPOSTRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestreference' => '/PullRequest/REST/v1/PullRequestReference.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestrepresentation' => '/PullRequest/REST/v1/PullRequestRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestshortstatrepresentation' => '/PullRequest/REST/v1/PullRequestShortStatRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestsresource' => '/PullRequest/REST/v1/PullRequestsResource.php',
            'tuleap\\pullrequest\\rest\\v1\\repositorypullrequestrepresentation' => '/PullRequest/REST/v1/RepositoryPullRequestRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\repositoryresource' => '/PullRequest/REST/v1/RepositoryResource.php',
            'tuleap\\pullrequest\\router' => '/PullRequest/Router.php',
            'tuleap\\pullrequest\\shortstat' => '/PullRequest/ShortStat.php',
            'tuleap\\pullrequest\\unidiffline' => '/PullRequest/UniDiffLine.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloadebab9743706eeeb9ce22bdce9d09d6e6');
// @codeCoverageIgnoreEnd
