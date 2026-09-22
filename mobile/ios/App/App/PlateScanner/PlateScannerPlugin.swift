import Foundation
import Capacitor

/// Exposes the native plate scanner to the wrapped web app as
/// `window.Capacitor.Plugins.PlateScanner.scan()`, resolving with either
/// `{ plate: "AB12CDE", cancelled: false }` or `{ plate: null, cancelled: true }`.
@objc(PlateScannerPlugin)
public class PlateScannerPlugin: CAPPlugin, CAPBridgedPlugin {
    public let identifier = "PlateScannerPlugin"
    public let jsName = "PlateScanner"
    public let pluginMethods: [CAPPluginMethod] = [
        CAPPluginMethod(name: "scan", returnType: CAPPluginReturnPromise),
    ]

    @objc func scan(_ call: CAPPluginCall) {
        DispatchQueue.main.async {
            guard let presentingViewController = self.bridge?.viewController else {
                call.reject("No view controller available to present the scanner.")
                return
            }

            let scanner = PlateScannerViewController()
            scanner.modalPresentationStyle = .fullScreen
            scanner.onResult = { plate in
                scanner.dismiss(animated: true) {
                    if let plate {
                        call.resolve(["plate": plate, "cancelled": false])
                    } else {
                        call.resolve(["plate": NSNull(), "cancelled": true])
                    }
                }
            }
            presentingViewController.present(scanner, animated: true)
        }
    }
}
