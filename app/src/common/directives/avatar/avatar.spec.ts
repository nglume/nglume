namespace common.directives.avatar {

    interface TestScope extends ng.IRootScopeService {
        testUser: common.models.User;
        AvatarController: AvatarController;
    }

    describe('Change avatar controller', () => {

        let $compile:ng.ICompileService,
            $rootScope:global.IRootScope,
            $scope:ng.IScope,
            AvatarController:AvatarController,
            directiveScope:TestScope,
            compiledElement:ng.IAugmentedJQuery,
            $q:ng.IQService;

        beforeEach(() => {

            module('app');

            inject((_$compile_, $controller, _$rootScope_, _$mdDialog_, _$q_) => {
                $compile = _$compile_;
                $rootScope = _$rootScope_;
                $scope = $rootScope.$new();

                $q = _$q_;
            });

            // Only initialise the directive once to speed up the testing
            if(!AvatarController) {

                directiveScope = <TestScope>$rootScope.$new();

                directiveScope.testUser = common.models.UserMock.entity({
                    _uploadedAvatar: common.models.ImageMock.entity()
                });

                compiledElement = $compile(`
                        <avatar ng-model="testUser" can-edit="true"></avatar>
                    `)(directiveScope);

                $rootScope.$digest();

                AvatarController = (<TestScope>compiledElement.isolateScope()).AvatarController;

                (<any>AvatarController).$mdDialog.show = sinon.stub().returns($q.when(true));
                (<any>AvatarController).$mdDialog.hide = sinon.stub();

            }

        });

        describe('Initialization', () => {

            it('should initialise the directive', () => {

                expect($(compiledElement).hasClass('avatar-directive')).to.be.true;

                expect(AvatarController.canEdit).to.be.true;

                expect(AvatarController.width).to.equal(200);

                expect(AvatarController.height).to.equal(200);

            });

            it('should not be able to edit the avatar by default', () => {

                let displayOnlyCompiledElement:ng.IAugmentedJQuery = $compile(`
                    <avatar ng-model="testUser"></avatar>
                `)(directiveScope);

                $rootScope.$digest();

                let DisplayOnlyAvatarController = (<TestScope>displayOnlyCompiledElement.isolateScope()).AvatarController;

                expect(DisplayOnlyAvatarController.canEdit).to.be.false;

            });

            it('should not be able to set the avatar dimensions', () => {

                let displayOnlyCompiledElement:ng.IAugmentedJQuery = $compile(`
                    <avatar ng-model="testUser" width="100" height="150"></avatar>
                `)(directiveScope);

                $rootScope.$digest();

                let DisplayOnlyAvatarController = (<TestScope>displayOnlyCompiledElement.isolateScope()).AvatarController;

                expect(DisplayOnlyAvatarController.width).to.equal(100);

                expect(DisplayOnlyAvatarController.height).to.equal(150);

            });

        });

        describe('Dialog functions', () => {

            it('should be able to open the avatar dialog', () => {

                AvatarController.openAvatarDialog();

                $rootScope.$digest();

                expect((<any>AvatarController).$mdDialog.show).to.be.called;
            });

            it('should be able to close the avatar dialog', () => {

                AvatarController.closeAvatarDialog();

                $rootScope.$digest();

                expect((<any>AvatarController).$mdDialog.hide).to.be.called;
            });

        });

        describe('Avatar functions', () => {

            it('should be able to save the update to the avatar', () => {

                AvatarController.updatedAvatar();

                $rootScope.$digest();

                expect(AvatarController.user.avatarImgId).to.equal(AvatarController.user._uploadedAvatar.imageId);

                expect((<any>AvatarController).$mdDialog.hide).to.be.called;

            });

            it('should be able to remove the avatar', () => {

                let removePromise = AvatarController.removeAvatar();

                $rootScope.$digest();

                expect((<any>AvatarController).$mdDialog.show).to.be.called;

                removePromise.then(() => {

                    expect(AvatarController.user.avatarImgId).to.equal(null);

                    expect(AvatarController.user._uploadedAvatar).to.equal(null);

                    expect((<any>AvatarController).$mdDialog.hide).to.be.called;

                });

            });

        });

    });

}