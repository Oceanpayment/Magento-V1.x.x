<h2>Overview</h4>
Oceanpayment supports mainstream open-source payment plug-ins, such as Magento, WordPress, OpenCart, PrestaShop, and Zen Cart, which are easy to install and save development costs and resources. 
<h2>Plug-in installation below 2.0.</h2>
<h4>Introduce</h4>
Magento is a professional open source e-commerce system. Magento is designed to be very flexible, with a modular architecture system and functions. It is easy to integrate seamlessly with third-party application systems. It is oriented to enterprise-level applications and can handle various needs and build a multi-purpose and applicable e-commerce website.
<ul>
  <li>Supports Card Payments and Alternative Payments embedded plug-ins.</li>
  <li>Support email sending.</li>
</ul>
<h4>Plug-in installation</h4>
<ol>
    <li>Overwrite the downloaded file to the root directory of the magento website. 
      <ul>
        <li>Overwrite the downloaded file to the root directory of the Magento website and copy the “op_ideal” folder to the template path in use by the website. For example, app\design\frontend\template-name-in-use-by-the-website\default\template\op_ideal.
        </li>
        <li>The “images” folder also needs to be copied to the template path in use. For example, skin\frontend\template-name-in-use\default\images.
        </li>
      </ul>
    </li>
    <li>Run php bin/magento setup:upgrade in the root directory of the website and wait for all modules to be loaded.</li>
    <li>Clear the background cache System->Cache Management.</li>
    <li>Go to Configuration Stores->Configuration->Payment Methods.</li>
</ol>
<table>
  <tr>
    <td>Enable</td>
    <td>Yes</td>
  </tr>
  <tr>
    <td>Title</td>
    <td>Alipay</td>
  </tr>
  <tr>
    <td>Pay Mode</td>
    <td>Redirect:Redirect to open payment page.</td>
  </tr>
  <tr>
    <td>Account</td>
    <td>Provide by Oceanpayment technical support.</td>
  </tr>
  <tr>
    <td>Terminal</td>
    <td>Provide by Oceanpayment technical support.</td>
  </tr>
  <tr>
    <td>SecureCode</td>
    <td>Provide by Oceanpayment technical support.</td>
  </tr>
  <tr>
    <td>Transport URL</td>
    <td>Production environment：https://secure.oceanpayment.com/gateway/service/pay<br>
      Sandbox environment：https://test-secure.oceanpayment.com/gateway/service/pay</td>
  </tr>
  
  <tr>
    <td>New Order Status</td>
    <td>On Hold</td>
  </tr>
  <tr>
    <td>Approved Order Status</td>
    <td>Processing</td>
  </tr>
  <tr>
    <td>Failure Order Status</td>
    <td>Canceled</td>
  </tr>
  <tr>
    <td>Pre-auth Order Status</td>
    <td>Pending</td>
  </tr>
  <tr>
    <td>High Risk Order Status</td>
    <td>Canceled</td>
  </tr>
  <tr>
    <td>Iframe Height(px)</td>
    <td></td>
  </tr>
  <tr>
    <td>Send Order Email</td>
    <td>Yes</td>
  </tr>
  <tr>
    <td>Automatic Invoice</td>
    <td>Yes</td>
  </tr>
  <tr>
    <td>3D Secure mode</td>
    <td>Disable</td>
  </tr>
</table>

