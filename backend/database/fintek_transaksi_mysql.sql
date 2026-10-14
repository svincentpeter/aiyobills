-- ==========================================================
-- Database SQL Export for AiYO Bills Transactions Ledger
-- Database Name: fintek (atau mesm7948_msra)
-- Table: transaksi
-- ==========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE TABLE IF NOT EXISTS `transaksi` (
  `referenceId` varchar(25) NOT NULL,
  `userName` varchar(50) NOT NULL,
  `userEmail` varchar(40) NOT NULL,
  `userPhone` varchar(20) NOT NULL,
  `remarks` text NOT NULL,
  `payAmount` bigint UNSIGNED NOT NULL,
  `items` text NOT NULL,
  `invoiceId` varchar(25) NOT NULL,
  `invoiceURL` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status` varchar(10) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoiceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed Data
INSERT INTO `transaksi` (`referenceId`, `userName`, `userEmail`, `userPhone`, `remarks`, `payAmount`, `items`, `invoiceId`, `invoiceURL`, `status`, `timestamp`) VALUES
('MSRA101417184266', 'Catherine Wong', 'cthrnwong11@gmail.com', '085329000345', 'Sewa Ruangan Silent Focus Pod Solo #A', 22200, '[{\"itemName\":\"Sewa Ruangan: Silent Focus Pod Solo #A\",\"itemType\":\"ITEM\",\"itemCount\":1,\"itemTotalPrice\":22200}]', 'wRTQOppTkupuv3dyFikE', 'https://bills-invoice.aiyo.id/bills/invoice/wRTQOppTkupuv3dyFikE', 'PAID', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `status` = 'PAID';
