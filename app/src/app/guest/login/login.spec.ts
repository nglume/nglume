

describe('Login', () => {

    describe('Configuration', () => {

        let LoginController:ng.IControllerService,
            $scope:ng.IScope,
            $mdDialog:ng.material.IDialogService,
            authService:NgJwtAuth.NgJwtAuthService
        ;

        beforeEach(() => {

            module('app');
        });

        beforeEach(()=> {
            inject(($controller, $rootScope, _ngJwtAuthService_, _$mdDialog_) => {
                $scope = $rootScope.$new();
                $mdDialog = _$mdDialog_;
                authService = _ngJwtAuthService_;
                LoginController = $controller(app.guest.login.namespace+'.controller', {$scope: $scope, $mdDialog: $mdDialog});
            })
        });

        it('should be a valid controller', () => {

            expect(LoginController).to.be.ok;
        });

        it('should have initialised the auth service', () => {

            expect((<any>authService).refreshTimerPromise).to.be.ok;

        });

        describe('dialog interactions', () => {

            beforeEach(() => {

                sinon.spy($mdDialog, 'hide');
                sinon.spy($mdDialog, 'cancel');
                sinon.spy($mdDialog, 'show');

            });

            afterEach(() => {

                (<any>$mdDialog).hide.restore();
                (<any>$mdDialog).cancel.restore();
                (<any>$mdDialog).show.restore();

            });

            it('should resolve the dialog when login credentials are passed', () => {


                let creds = {
                    username: 'foo',
                    password: 'bar',
                };

                (<any>$scope).login(creds.username, creds.password);

                expect($mdDialog.hide).to.have.been.calledWith(creds);

            });

            it('should cancel dialog when requested', () => {


                (<any>$scope).cancelLoginDialog();

                expect($mdDialog.cancel).to.have.been.called;

            });

            it('should show the login dialog when prompted', () => {


                authService.promptLogin();

                expect($mdDialog.show).to.have.been.called;

            });

        });


    });

});